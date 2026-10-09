<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Twig\Components\Organisms\CombatSimulator;

use App\Modules\Ares\Application\Battle\BattleShipTypeRegistry;
use App\Modules\Ares\Application\Battle\GetBattleShipTypeName;
use App\Modules\Ares\Domain\Model\Battle\BattleShipType;
use App\Modules\Ares\Domain\Model\Battle\FormationSlot;
use App\Modules\Ares\Domain\Model\Battle\FormationViolation;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Modules\Ares\Domain\Model\Battle\OfficerTrait;
use App\Modules\Ares\Domain\Service\Battle\BuildFleetFormation;
use App\Modules\Ares\Domain\Service\Battle\ValidateFormation;
use App\Modules\Ares\Infrastructure\Twig\FormationGrid;
use App\Modules\Portal\Infrastructure\CombatSimulator\SimulatedFleetStorage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Composition of a simulated fleet: officer, side, arrival turn and formation (D9).
 */
#[AsLiveComponent(
	name: 'SimulatorFleetEditor',
	template: 'components/Organisms/Portal/CombatSimulator/FleetEditor.html.twig',
)]
final class FleetEditor
{
	use DefaultActionTrait;
	use ComponentToolsTrait;

	private const int CELL_SIZE = 34;
	private const int MAX_OFFICER_LEVEL = 20;

	#[LiveProp]
	public ?string $fleetId = null;
	#[LiveProp(writable: true)]
	public string $name = '';
	#[LiveProp(writable: true)]
	public int $side = 0;
	#[LiveProp(writable: true)]
	public int $faction = 0;
	#[LiveProp(writable: true)]
	public int $officerLevel = 1;
	#[LiveProp(writable: true)]
	public int $arrivalTurn = 1;
	/** @var list<string> officer traits (Q13), chosen by the player in the simulator */
	#[LiveProp]
	public array $traits = [];
	/** @var list<array{type: string, ships: int, q: int, r: int}> */
	#[LiveProp]
	public array $formation = [];
	#[LiveProp(writable: true)]
	public string $selectedType = 'LightFighter';
	/** What a click on a cell does: place a squadron or remove it */
	#[LiveProp(writable: true)]
	public string $tool = 'place';
	/** Ships of the next squadron placed, 0 for a full squadron */
	#[LiveProp(writable: true)]
	public int $squadronShips = 0;
	/** @var list<string> */
	public array $errors = [];

	/**
	 * @param array<int, array<string, mixed>> $factionsConfiguration
	 */
	public function __construct(
		private readonly SimulatedFleetStorage $simulatedFleetStorage,
		private readonly BattleShipTypeRegistry $battleShipTypeRegistry,
		private readonly GetBattleShipTypeName $getBattleShipTypeName,
		private readonly BuildFleetFormation $buildFleetFormation,
		private readonly ValidateFormation $validateFormation,
		private readonly TranslatorInterface $translator,
		#[Autowire('%ares.battle.formation_radius%')]
		private readonly int $formationRadius,
		#[Autowire('%app.factions%')]
		private readonly array $factionsConfiguration,
	) {
	}

	#[LiveListener('newSimulatedFleet')]
	public function newFleet(#[LiveArg] int $side): void
	{
		$this->fleetId = null;
		$this->name = '';
		$this->side = $side;
		$this->faction = 0;
		$this->officerLevel = 1;
		$this->arrivalTurn = 1;
		$this->traits = [];
		$this->formation = [];
		$this->errors = [];

		$this->dispatchBrowserEvent('modal:open');
	}

	#[LiveListener('editSimulatedFleet')]
	public function editFleet(#[LiveArg] string $fleetId): void
	{
		if (null === ($fleet = $this->simulatedFleetStorage->get($fleetId))) {
			return;
		}

		$this->fleetId = $fleet['id'];
		$this->name = $fleet['name'];
		$this->side = $fleet['side'];
		$this->faction = $fleet['faction'];
		$this->officerLevel = $fleet['officer_level'];
		$this->arrivalTurn = $fleet['arrival_turn'];
		$this->traits = $fleet['traits'] ?? [];
		$this->formation = $fleet['formation'];
		$this->errors = [];

		$this->dispatchBrowserEvent('modal:open');
	}

	#[LiveAction]
	public function clickCell(#[LiveArg] int $q, #[LiveArg] int $r): void
	{
		if ('remove' === $this->tool) {
			$this->removeSquadron($q, $r);

			return;
		}

		$this->placeSquadron($q, $r);
	}

	#[LiveAction]
	public function placeSquadron(#[LiveArg] int $q, #[LiveArg] int $r): void
	{
		$type = $this->battleShipTypeRegistry->get($this->selectedType);
		$ships = $this->squadronShips > 0 ? min($this->squadronShips, $type->squadronSize) : $type->squadronSize;

		$this->removeSquadron($q, $r);
		$this->formation[] = ['type' => $type->identifier, 'ships' => $ships, 'q' => $q, 'r' => $r];
	}

	#[LiveAction]
	public function removeSquadron(#[LiveArg] int $q, #[LiveArg] int $r): void
	{
		$this->formation = array_values(array_filter(
			$this->formation,
			fn (array $slot) => $slot['q'] !== $q || $slot['r'] !== $r,
		));
	}

	/**
	 * Rearranges the squadrons by role, as an officer would (the "fill automatically" suggestion of D9).
	 */
	#[LiveAction]
	public function autoArrange(): void
	{
		$ships = [];
		foreach ($this->formation as $slot) {
			$ships[$slot['type']] = ($ships[$slot['type']] ?? 0) + $slot['ships'];
		}

		try {
			$formation = ($this->buildFleetFormation)(array_map(
				fn (string $type, int $quantity) => [$this->battleShipTypeRegistry->get($type), $quantity],
				array_keys($ships),
				array_values($ships),
			));
		} catch (\DomainException $exception) {
			$this->errors = [$exception->getMessage()];

			return;
		}

		$this->formation = array_map(fn (FormationSlot $slot) => [
			'type' => $slot->type->identifier,
			'ships' => $slot->ships,
			'q' => $slot->offset->q,
			'r' => $slot->offset->r,
		], $formation);
	}

	#[LiveAction]
	public function clear(): void
	{
		$this->formation = [];
	}

	#[LiveAction]
	public function save(): void
	{
		$this->errors = $this->validate();

		if ([] !== $this->errors) {
			return;
		}

		$this->simulatedFleetStorage->save([
			'id' => $this->fleetId ?? Uuid::v4()->toRfc4122(),
			'name' => trim($this->name),
			'side' => $this->side,
			'faction' => $this->faction,
			'officer_level' => $this->officerLevel,
			'arrival_turn' => $this->arrivalTurn,
			'traits' => $this->traits,
			'formation' => $this->formation,
		]);

		$this->emit('simulatedFleetSaved', componentName: 'SimulatorFleetsList');
		$this->dispatchBrowserEvent('modal:close');
	}

	/**
	 * @return list<array{q: int, r: int, left: float, top: float, slot: array{type: string, ships: int, q: int, r: int}|null}>
	 */
	public function getCells(): array
	{
		return (new FormationGrid($this->formationRadius, self::CELL_SIZE))->getCells(
			fn (int $q, int $r) => array_find($this->formation, fn (array $slot) => $slot['q'] === $q && $slot['r'] === $r),
		);
	}

	/**
	 * @return array{width: float, height: float, cell_width: float, cell_height: float}
	 */
	public function getGridDimensions(): array
	{
		return (new FormationGrid($this->formationRadius, self::CELL_SIZE))->getDimensions();
	}

	/**
	 * @return array<string, BattleShipType>
	 */
	public function getShipTypes(): array
	{
		return $this->battleShipTypeRegistry->all();
	}

	public function getShipTypeName(string $identifier): string
	{
		return ($this->getBattleShipTypeName)($this->battleShipTypeRegistry->get($identifier));
	}

	/**
	 * @return list<int>
	 */
	public function getFactions(): array
	{
		return array_keys($this->factionsConfiguration);
	}

	public function getTotalPev(): int
	{
		return array_sum(array_map(
			fn (array $slot) => $slot['ships'] * $this->battleShipTypeRegistry->get($slot['type'])->pev,
			$this->formation,
		));
	}

	/**
	 * Adds a trait to the officer, or removes it when already chosen. An officer has at most 3 traits.
	 */
	#[LiveAction]
	public function toggleTrait(#[LiveArg] string $trait): void
	{
		if (null === OfficerTrait::tryFrom($trait)) {
			return;
		}

		if (in_array($trait, $this->traits, true)) {
			$this->traits = array_values(array_diff($this->traits, [$trait]));

			return;
		}

		if (count($this->traits) < OfficerTrait::MAX_PER_OFFICER) {
			$this->traits[] = $trait;
		}
	}

	/**
	 * @return list<OfficerTrait>
	 */
	public function getAvailableTraits(): array
	{
		return OfficerTrait::cases();
	}

	public function getMaxTraits(): int
	{
		return OfficerTrait::MAX_PER_OFFICER;
	}

	public function getMaxOfficerLevel(): int
	{
		return self::MAX_OFFICER_LEVEL;
	}

	/**
	 * @return list<string>
	 */
	private function validate(): array
	{
		$errors = [];

		if ('' === trim($this->name)) {
			$errors[] = 'Donnez un nom à la flotte.';
		}

		if ($this->officerLevel < 1 || $this->officerLevel > self::MAX_OFFICER_LEVEL) {
			$errors[] = sprintf('Le niveau de l’officier doit être compris entre 1 et %d.', self::MAX_OFFICER_LEVEL);
		}

		if ($this->arrivalTurn < 1) {
			$errors[] = 'Le tour d’arrivée doit être supérieur ou égal à 1.';
		}

		if (!in_array($this->side, [0, 1], true)) {
			$errors[] = 'Choisissez un camp.';
		}

		if (!array_key_exists($this->faction, $this->factionsConfiguration)) {
			$errors[] = 'Choisissez une faction.';
		}

		$violations = ($this->validateFormation)(array_map(
			fn (array $slot) => new FormationSlot($this->battleShipTypeRegistry->get($slot['type']), $slot['ships'], new Hex($slot['q'], $slot['r'])),
			$this->formation,
		));

		return [...$errors, ...array_map(
			fn (FormationViolation $violation) => $this->translator->trans(
				sprintf('combat_simulator.formation_violations.%s', $violation->code),
				[
					...$violation->parameters,
					'type' => isset($violation->parameters['type']) ? $this->getShipTypeName((string) $violation->parameters['type']) : '',
				],
			),
			$violations,
		)];
	}
}
