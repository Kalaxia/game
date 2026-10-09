<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Mission;

use App\Modules\Ares\Application\Handler\Movement\MoveFleet;
use App\Modules\Ares\Domain\Event\Fleet\LootEvent;
use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Service\GetCommanderPev;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Event\PlanetOwnerChangeEvent;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Galaxy\Domain\Service\UpdatePlanetPoints;
use App\Modules\Galaxy\Manager\PlaceManager;
use App\Modules\Galaxy\Manager\PlanetManager;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonusId;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Carries out the mission of a fleet at its destination, once nothing stands in its way:
 * there was no defender, or its side won the battle (R7).
 *
 * The battle itself is decided by ArriveAtPlanet; the outcomes are those of the legacy game.
 */
readonly class AccomplishMission
{
	public function __construct(
		private CommanderManager $commanderManager,
		private CommanderRepositoryInterface $commanderRepository,
		private MoveFleet $moveFleet,
		private PlaceManager $placeManager,
		private PlanetManager $planetManager,
		private PlanetRepositoryInterface $planetRepository,
		private UpdatePlanetPoints $updatePlanetPoints,
		private PlayerBonusManager $playerBonusManager,
		private GetCommanderPev $getCommanderPev,
		private EventDispatcherInterface $eventDispatcher,
	) {
	}

	/**
	 * @param Battle|null $battle the battle won before, linked in the notifications
	 */
	public function __invoke(Commander $commander, ?Battle $battle = null): void
	{
		match ($commander->travelType) {
			CommanderMission::Move => $this->commanderManager->uChangeBase($commander),
			CommanderMission::Loot => $this->loot($commander, $battle),
			CommanderMission::Colo => $this->conquer($commander, $battle),
			CommanderMission::Back => $this->comeBack($commander),
			default => throw new \LogicException(sprintf('Commander %s has arrived without mission', $commander->id->toRfc4122())),
		};

		$this->commanderRepository->save($commander);
	}

	/**
	 * Whether the mission of the fleet is an attack on its destination: looting or conquering a planet
	 * of a rebel or of an enemy player that can be attacked. Same rules as the legacy game: a player
	 * cannot attack their own faction or an allied one, nor a player under level 2 (loot) or 4 (conquest).
	 */
	public static function isAttack(Commander $commander): bool
	{
		$planet = $commander->destinationPlace;

		if (!in_array($commander->travelType, [CommanderMission::Loot, CommanderMission::Colo], true) || null === $planet) {
			return false;
		}

		if (null === ($owner = $planet->player)) {
			return true;
		}

		$minimumLevel = CommanderMission::Loot === $commander->travelType ? 1 : 3;
		$attackerFaction = $commander->player->faction;

		return !$owner->faction->id->equals($attackerFaction->id)
			&& $owner->level > $minimumLevel
			&& Color::ALLY !== ($attackerFaction->relations[$owner->faction->identifier] ?? null);
	}

	private function loot(Commander $commander, ?Battle $battle): void
	{
		$planet = $commander->destinationPlace;
		$owner = $planet->player;

		if (!self::isAttack($commander)) {
			$this->stopAtFriendlyPlanet($commander);

			return;
		}

		$capacity = $this->getLootCapacity($commander);

		if (null === $owner) {
			$looted = min($capacity, $planet->resourcesStorage);
			$planet->resourcesStorage -= $looted;
		} else {
			$looted = max(0, min($capacity, $planet->resourcesStorage - Commander::LIMITTOLOOT));
			if ($looted > 0) {
				$this->planetManager->decreaseResources($planet, $looted);
			}
		}
		$commander->resources = $looted;

		$this->goBack($commander);
		$this->placeManager->sendNotif($planet, match (true) {
			null === $owner => Planet::LOOTEMPTYSSUCCESS,
			null !== $battle => Planet::LOOTPLAYERWHITBATTLESUCCESS,
			default => Planet::LOOTPLAYERWHITOUTBATTLESUCCESS,
		}, $commander, $battle);

		$this->eventDispatcher->dispatch(new LootEvent($commander, $owner));
	}

	private function conquer(Commander $commander, ?Battle $battle): void
	{
		$planet = $commander->destinationPlace;
		$formerOwner = $planet->player;

		if (!self::isAttack($commander)) {
			$this->stopAtFriendlyPlanet($commander);

			return;
		}

		if (null === $formerOwner) {
			// Colonisation
			$planet->player = $commander->player;
			$planet->name = 'colonie';
			$planet->iSchool = 500;
			$planet->iAntiSpy = 500;
			$planet->resourcesStorage = 2000;
			$planet->danger = 0;
			$this->updatePlanetPoints->updatePoints($planet);
			$this->planetRepository->save($planet);
		} else {
			$this->planetManager->changeOwner($planet, $commander->player);
		}

		$commander->base = $planet;
		$this->commanderManager->endTravel($commander, Commander::AFFECTED);

		if (null === $formerOwner) {
			$this->placeManager->sendNotif($planet, Planet::CONQUEREMPTYSSUCCESS, $commander, $battle);
		} elseif (null !== $battle) {
			$this->placeManager->sendNotifForConquest($planet, Planet::CONQUERPLAYERWHITBATTLESUCCESS, $commander, $battle);
		} else {
			$this->placeManager->sendNotif($planet, Planet::CONQUERPLAYERWHITOUTBATTLESUCCESS, $commander);
		}

		$this->eventDispatcher->dispatch(new PlanetOwnerChangeEvent($planet, $formerOwner));
	}

	/**
	 * A planet of the player is a new base for the fleet; the planet of an ally or of a protected player
	 * is not a target, and the fleet goes back.
	 */
	private function stopAtFriendlyPlanet(Commander $commander): void
	{
		$planet = $commander->destinationPlace;

		if ($planet->player?->id === $commander->player->id) {
			$this->commanderManager->uChangeBase($commander);

			return;
		}

		$this->goBack($commander);
		$this->placeManager->sendNotif($planet, Planet::CHANGELOST, $commander);
	}

	private function comeBack(Commander $commander): void
	{
		$this->placeManager->sendNotif($commander->destinationPlace, Planet::COMEBACK, $commander);

		$this->commanderManager->endTravel($commander, Commander::AFFECTED);

		if ($commander->resources > 0) {
			$this->planetManager->increaseResources($commander->base, $commander->resources);
			$commander->resources = 0;
		}
	}

	private function goBack(Commander $commander): void
	{
		// The way back starts now, not at the arrival: a battle may have taken place meanwhile
		$commander->arrivedAt = new \DateTimeImmutable();
		($this->moveFleet)(
			commander: $commander,
			origin: $commander->destinationPlace,
			destination: $commander->startPlace ?? $commander->base,
			mission: CommanderMission::Back,
		);
	}

	/**
	 * Resources a fleet can carry: 275 per PEV left, plus the container bonus of the player.
	 */
	private function getLootCapacity(Commander $commander): int
	{
		$bonus = $this->playerBonusManager->getBonusByPlayer($commander->player)->bonuses->get(PlayerBonusId::SHIP_CONTAINER) ?? 0;
		$capacity = ($this->getCommanderPev)($commander) * Commander::COEFFLOOT;

		return $capacity + intval(round($capacity * 2 * $bonus / 100));
	}
}
