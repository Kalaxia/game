<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components\Organisms;

use App\Modules\Ares\Application\Battle\BattleShipTypeRegistry;
use App\Modules\Ares\Application\Battle\GetBattleRules;
use App\Modules\Ares\Application\Battle\GetBattleShipTypeName;
use App\Modules\Ares\Domain\Event\Fleet\SquadronUpdateEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleShipType;
use App\Modules\Ares\Domain\Model\Battle\OfficerTrait;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Service\Battle\BuildFleetFormation;
use App\Modules\Ares\Domain\Service\GetCommanderPev;
use App\Modules\Ares\Infrastructure\Twig\FormationGrid;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Composition of an officer's fleet in orbit of its base (D9): the player places squadrons of a single
 * ship type on the formation grid, with the ships of the planet hangar. Every change is saved at once,
 * and the composition is locked while the fleet travels or fights.
 */
#[AsLiveComponent(
	name: 'FleetFormation',
	template: 'components/Organisms/Fleet/FleetFormation.html.twig',
)]
final class FleetFormation
{
	use DefaultActionTrait;

	private const int CELL_SIZE = 34;

	#[LiveProp]
	public Commander $commander;
	#[LiveProp(writable: true)]
	public int $selectedCategory = 0;
	/** Ships of the next squadron placed, 0 for as many as possible */
	#[LiveProp(writable: true)]
	public int $squadronShips = 0;
	/** What a click on a cell does: place a squadron or remove it */
	#[LiveProp(writable: true)]
	public string $tool = 'place';
	public ?string $error = null;

	public function __construct(
		private readonly BattleShipTypeRegistry $battleShipTypeRegistry,
		private readonly GetBattleShipTypeName $getBattleShipTypeName,
		private readonly BuildFleetFormation $buildFleetFormation,
		private readonly GetBattleRules $getBattleRules,
		private readonly GetCommanderPev $getCommanderPev,
		private readonly CommanderManager $commanderManager,
		private readonly CommanderRepositoryInterface $commanderRepository,
		private readonly CurrentPlayerRegistry $currentPlayerRegistry,
		private readonly EventDispatcherInterface $eventDispatcher,
		private readonly EntityManagerInterface $entityManager,
		private readonly ClockInterface $clock,
		#[Autowire('%ares.battle.formation_radius%')]
		private readonly int $formationRadius,
	) {
	}

	#[LiveAction]
	public function clickCell(#[LiveArg] int $q, #[LiveArg] int $r): void
	{
		$this->checkCanCompose();

		if (!$this->getGrid()->isOnGrid($q, $r)) {
			return;
		}

		'remove' === $this->tool ? $this->removeSquadron($q, $r) : $this->placeSquadron($q, $r);
		$this->save();
	}

	/**
	 * Rearranges the squadrons by role, as an officer would (the "fill automatically" suggestion of D9).
	 */
	#[LiveAction]
	public function autoArrange(): void
	{
		$this->checkCanCompose();

		$ships = [];
		foreach ($this->commander->squadrons as $squadron) {
			$type = $this->battleShipTypeRegistry->getByCategory($squadron->shipCategory)->identifier;
			$ships[$type] = ($ships[$type] ?? 0) + $squadron->ships;
		}

		try {
			$formation = ($this->buildFleetFormation)(array_map(
				fn (string $type, int $quantity) => [$this->battleShipTypeRegistry->get($type), $quantity],
				array_keys($ships),
				array_values($ships),
			));
		} catch (\DomainException) {
			$this->error = 'Ces vaisseaux ne tiennent pas dans la grille de formation.';

			return;
		}

		$this->commander->squadrons->clear();
		foreach ($formation as $slot) {
			$this->addSquadron($slot->type->shipCategory, $slot->ships, $slot->offset->q, $slot->offset->r);
		}
		$this->save();
	}

	/**
	 * Sends every ship back to the hangar.
	 */
	#[LiveAction]
	public function clear(): void
	{
		$this->checkCanCompose();

		$this->commanderManager->emptySquadrons($this->commander);
		$this->save();
	}

	public function canCompose(): bool
	{
		return $this->commander->isAffected()
			&& null !== $this->commander->base
			&& $this->commander->player?->id === $this->currentPlayerRegistry->get()->id;
	}

	/**
	 * @return list<array{q: int, r: int, left: float, top: float, slot: Squadron|null}>
	 */
	public function getCells(): array
	{
		return $this->getGrid()->getCells(fn (int $q, int $r) => $this->commander->getSquadronAt($q, $r));
	}

	/**
	 * @return array{width: float, height: float, cell_width: float, cell_height: float}
	 */
	public function getGridDimensions(): array
	{
		return $this->getGrid()->getDimensions();
	}

	/**
	 * Ship types with the ships waiting in the hangar of the base.
	 *
	 * @return list<array{category: ShipCategory, type: BattleShipType, name: string, hangar: int}>
	 */
	public function getShipTypes(): array
	{
		$storage = $this->commander->base?->getShipStorage() ?? [];

		return array_map(fn (ShipCategory $category) => [
			'category' => $category,
			'type' => $type = $this->battleShipTypeRegistry->getByCategory($category),
			'name' => ($this->getBattleShipTypeName)($type),
			'hangar' => $storage[$category->value] ?? 0,
		], ShipCategory::cases());
	}

	public function getShipTypeName(ShipCategory $category): string
	{
		return ($this->getBattleShipTypeName)($this->battleShipTypeRegistry->getByCategory($category));
	}

	public function getPictogram(ShipCategory $category): string
	{
		return $this->battleShipTypeRegistry->getByCategory($category)->pictogram;
	}

	public function getPev(): int
	{
		return ($this->getCommanderPev)($this->commander);
	}

	/**
	 * Command capacity of the officer (D10), and the malus of the fleet beyond it.
	 *
	 * @return array{capacity: int, malus: int}|null
	 */
	public function getCommand(): ?array
	{
		if (null === ($rules = ($this->getBattleRules)()->command)) {
			return null;
		}

		$factor = in_array(OfficerTrait::Logistician->value, $this->commander->traits, true) ? 1.2 : 1.0;
		$level = max(1, $this->commander->level);

		return [
			'capacity' => $rules->getCapacity($level, $factor),
			'malus' => (int) round(100 * $rules->getMalus($this->getPev(), $level, $factor)),
		];
	}

	private function placeSquadron(int $q, int $r): void
	{
		$category = ShipCategory::tryFrom($this->selectedCategory) ?? throw new \InvalidArgumentException('Unknown ship category');
		$type = $this->battleShipTypeRegistry->getByCategory($category);
		$base = $this->commander->base;

		$this->removeSquadron($q, $r);

		$wanted = $this->squadronShips > 0 ? min($this->squadronShips, $type->squadronSize) : $type->squadronSize;
		$ships = min($wanted, $base->getShipStorage()[$category->value] ?? 0);

		if ($ships < 1) {
			$this->error = sprintf('Il n’y a plus de %s dans le hangar.', ($this->getBattleShipTypeName)($type));

			return;
		}

		$base->removeShips($category->value, $ships);
		$squadron = $this->addSquadron($category, $ships, $q, $r);

		$this->eventDispatcher->dispatch(new SquadronUpdateEvent($this->commander, $squadron, $this->commander->player));
	}

	private function removeSquadron(int $q, int $r): void
	{
		if (null === ($squadron = $this->commander->getSquadronAt($q, $r))) {
			return;
		}

		$this->commander->base->addShips($squadron->shipCategory, $squadron->ships);
		$this->commander->squadrons->removeElement($squadron);
	}

	private function addSquadron(ShipCategory $category, int $ships, int $q, int $r): Squadron
	{
		$squadron = new Squadron(
			id: Uuid::v7(),
			commander: $this->commander,
			shipCategory: $category,
			ships: $ships,
			q: $q,
			r: $r,
			createdAt: $this->clock->now(),
			updatedAt: $this->clock->now(),
		);
		$this->commander->squadrons->add($squadron);

		return $squadron;
	}

	private function checkCanCompose(): void
	{
		if (!$this->canCompose()) {
			throw new AccessDeniedHttpException('This fleet cannot be composed now');
		}
	}

	private function save(): void
	{
		$this->commanderRepository->save($this->commander);
		$this->entityManager->flush();
	}

	private function getGrid(): FormationGrid
	{
		return new FormationGrid($this->formationRadius, self::CELL_SIZE);
	}
}
