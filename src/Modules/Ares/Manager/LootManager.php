<?php

declare(strict_types=1);

namespace App\Modules\Ares\Manager;

use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Application\Handler\Movement\MoveFleet;
use App\Modules\Ares\Domain\Event\Fleet\LootEvent;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\LiveReport;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

readonly class LootManager
{
	public function __construct(
		private EntityManagerInterface       $entityManager,
		private EventDispatcherInterface     $eventDispatcher,
		private CommanderManager             $commanderManager,
		private CommanderRepositoryInterface $commanderRepository,
		private MoveFleet $moveFleet,
		private OrbitalBaseManager           $orbitalBaseManager,
		private PlaceManager                 $placeManager,
		private PlayerBonusManager           $playerBonusManager,
		private CommanderArmyHandler $commanderArmyHandler,
	) {
	}

	public function loot(Commander $commander): void
	{
		$place = $commander->destinationPlace;
		$placeBase = $place->base;
		$placePlayer = $placeBase?->player;
		$placeCommanders = null !== $placeBase ? $this->commanderRepository->getBaseCommanders($placeBase) : [];
		// @WARNING possibly not the right property to use
		$commanderPlace = $commander->startPlace;
		$commanderPlayer = $commander->player;
		$commanderColor = $commanderPlayer->faction;
		$playerBonus = $this->playerBonusManager->getBonusByPlayer($commanderPlayer);
		LiveReport::$type = Commander::LOOT;
		LiveReport::$dFight = $commander->getArrivalDate();

		// si la planète est vide
		if (null === $placePlayer) {
			LiveReport::$isLegal = true;

			// planète vide : faire un combat
			$this->commanderManager->startFight($place, $commander);

			// victoire
			if (!$commander->isDead()) {
				// piller la planète
				$this->commanderManager->lootAnEmptyPlace($place, $commander, $playerBonus);
				// création du rapport de combat
				$report = $this->commanderManager->createReport($place);

				// réduction de la force de la planète
				$percentage = (($report->defenderPevAtEnd + 1) / ($report->defenderPevAtBeginning + 1)) * 100;
				$place->danger = intval(round(($percentage * $place->danger) / 100));

				($this->moveFleet)(
					commander: $commander,
					origin: $place,
					destination: $commanderPlace,
					mission: CommanderMission::Back,
				);
				$this->placeManager->sendNotif($place, Place::LOOTEMPTYSSUCCESS, $commander, $report);
			} else {
				// si il est mort
				// création du rapport de combat
				$report = $this->commanderManager->createReport($place);
				$this->placeManager->sendNotif($place, Place::LOOTEMPTYFAIL, $commander, $report);

				// réduction de la force de la planète
				// TODO Factorize in a service
				$percentage = (($report->defenderPevAtEnd + 1) / ($report->defenderPevAtBeginning + 1)) * 100;
				$place->danger = intval(round(($percentage * $place->danger) / 100));
			}
			// si il y a une base d'un joueur
		} else {
			// TODO Move to Specification class
			LiveReport::$isLegal = $commanderColor->canAttackLegally($place->base->player->faction);

			// planète à joueur : si $this->rColor != commandant->rColor
			// si il peut l'attaquer
			// TODO move to spec
			if ((!$place->base->player->faction->id->equals($commander->player->faction->id) && $place->base->player->level > 1 && Color::ALLY !== $commanderColor->relations[$place->base->player->faction->identifier]) || null === $place->base) {
				$dCommanders = [];
				foreach ($placeCommanders as $dCommander) {
					if ($dCommander->isAffected() && 1 == $dCommander->line) {
						$dCommanders[] = $dCommander;
					}
				}

				// il y a des commandants en défense : faire un combat avec un des commandants
				if (0 !== count($dCommanders)) {
					$aleaNbr = random_int(0, count($dCommanders) - 1);
					$this->commanderManager->startFight($place, $commander, $dCommanders[$aleaNbr]);

					// victoire
					if (!$commander->isDead()) {
						// piller la planète
						$this->lootAPlayerPlace($commander, $playerBonus, $placeBase);
						($this->moveFleet)(
							commander: $commander,
							origin: $place,
							destination: $commanderPlace,
							mission: CommanderMission::Back,
						);

						// suppression des commandants
						unset($placeCommanders[$aleaNbr]);
						$placeCommanders = array_merge($placeCommanders);

						// création du rapport
						$report = $this->commanderManager->createReport($place);

						$this->placeManager->sendNotif($place, Place::LOOTPLAYERWHITBATTLESUCCESS, $commander, $report);

					// défaite
					} else {
						// création du rapport
						$report = $this->commanderManager->createReport($place);

						$this->placeManager->sendNotif($place, Place::LOOTPLAYERWHITBATTLEFAIL, $commander, $report);
					}
				} else {
					$this->lootAPlayerPlace($commander, $playerBonus, $placeBase);
					($this->moveFleet)(
						commander: $commander,
						origin: $place,
						destination: $commanderPlace,
						mission: CommanderMission::Back,
					);
					$this->placeManager->sendNotif($place, Place::LOOTPLAYERWHITOUTBATTLESUCCESS, $commander);
				}
			} else {
				// si c'est la même couleur
				if ($place->base->player->id === $commander->player->id) {
					// si c'est une de nos planètes
					// on tente de se poser
					$this->commanderManager->uChangeBase($commander);
				} else {
					// si c'est une base alliée on repart
					($this->moveFleet)(
						commander: $commander,
						origin: $place,
						destination: $commanderPlace,
						mission: CommanderMission::Back,
					);
					$this->placeManager->sendNotif($place, Place::CHANGELOST, $commander);
				}
			}
		}
		$this->eventDispatcher->dispatch(new LootEvent($commander, $placePlayer));

		$this->entityManager->flush();
	}

	public function lootAPlayerPlace(Commander $commander, PlayerBonus $playerBonus, OrbitalBase $placeBase): void
	{
		$bonus = $playerBonus->bonuses->get(PlayerBonusId::SHIP_CONTAINER);

		$resourcesToLoot = $placeBase->resourcesStorage - Commander::LIMITTOLOOT;

		$storage = $this->commanderArmyHandler->getPevToLoot($commander) * Commander::COEFFLOOT;
		$storage += round($storage * ((2 * $bonus) / 100));

		$resourcesLooted = ($storage > $resourcesToLoot) ? $resourcesToLoot : $storage;

		if ($resourcesLooted > 0) {
			$this->orbitalBaseManager->decreaseResources($placeBase, $resourcesLooted);
			$commander->resources = $resourcesLooted;

			LiveReport::$resources = $resourcesLooted;
		}
	}
}
