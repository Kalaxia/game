<?php

declare(strict_types=1);

namespace App\Modules\Ares\Manager;

use App\Modules\Ares\Application\Handler\Movement\MoveFleet;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\LiveReport;
use App\Modules\Ares\Model\Report;
use App\Modules\Athena\Application\Handler\OrbitalBasePointsHandler;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Event\PlaceOwnerChangeEvent;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class ConquestManager
{
	public function __construct(
		private CommanderManager $commanderManager,
		private CommanderRepositoryInterface $commanderRepository,
		private MoveFleet $moveFleet,
		private PlaceManager $placeManager,
		private OrbitalBasePointsHandler $orbitalBasePointsHandler,
		private OrbitalBaseManager $orbitalBaseManager,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private PlayerBonusManager $playerBonusManager,
		private ReportRepositoryInterface $reportRepository,
		private EntityManagerInterface $entityManager,
		private EventDispatcherInterface $eventDispatcher,
		private NotificationManager $notificationManager,
		private int $colonizationCost,
		private int $conquestCost,
	) {
	}

	public function getColonizationCost(Player $player, int $totalBases): int
	{
		return $this->processAttackCost($player, $this->colonizationCost, $totalBases);
	}

	public function getConquestCost(Player $player, int $totalBases): int
	{
		return $this->processAttackCost($player, $this->conquestCost, $totalBases);
	}

	private function processAttackCost(Player $player, int $cost, int $totalBases): int
	{
		$price = $cost * $totalBases;

		if (ColorResource::ADRANITE == $player->faction->identifier) {
			// bonus if the player is from Cardan
			$price -= round($price * ColorResource::BONUS_CARDAN_COLO / 100);
		}

		return intval($price);
	}

	public function conquer(Commander $commander): void
	{
		$place = $commander->destinationPlace;
		$commanderColor = $commander->player->faction;
		$playerBonus = $this->playerBonusManager->getBonusByPlayer($commander->player);
		// conquete
		if (null !== ($placePlayer = $place->base?->player)) {
			// @TODO Replace with specification
			if ($placePlayer->faction !== $commander->player->faction
					&& $placePlayer->level > 3
					&& Color::ALLY != $commanderColor->relations[$placePlayer->faction->identifier]) {
				$reportIds = [];
				$reportArray = [];
				$placeBase = $place->base;
				$baseCommanders = $this->commanderRepository->getBaseCommanders($placeBase);

				for ($nbrBattle = 0; $nbrBattle < count($baseCommanders); ++$nbrBattle) {
					if (!$baseCommanders[$nbrBattle]->isAffected()) {
						continue;
					}
					LiveReport::$type = Commander::COLO;
					LiveReport::$dFight = $commander->getArrivalDate();
					LiveReport::$isLegal = $commanderColor->canAttackLegally($placePlayer->faction);

					$this->commanderManager->startFight($place, $commander, $baseCommanders[$nbrBattle]);

					$report = $this->commanderManager->createReport($place);
					$reportArray[] = $report;
					$reportIds[] = $report->id;
					// PATCH DEGUEU POUR LES MUTLIS-COMBATS
					$this->entityManager->clear();
					$reports = $this->reportRepository->getByAttackerAndPlace(
						$commander->player,
						$place,
						$commander->getArrivalDate(),
					);
					foreach ($reports as $r) {
						if ($r->id->equals($report->id)) {
							continue;
						}
						$r->attackerStatement = Report::DELETED;
						$r->defenderStatement = Report::DELETED;
					}
					$this->entityManager->flush();
					$this->entityManager->clear();

					// mort du commandant
					// arrêt des combats
					if ($commander->isDead()) {
						break;
					}
				}

				// victoire
				if (!$commander->isDead()) {
					if (0 == $nbrBattle) {
						$this->placeManager->sendNotif($place, Place::CONQUERPLAYERWHITOUTBATTLESUCCESS, $commander, null);
					} else {
						$this->placeManager->sendNotifForConquest($place, Place::CONQUERPLAYERWHITBATTLESUCCESS, $commander, $reportIds);
					}
					// changer l'appartenance de la base (et de la place)
					$this->orbitalBaseManager->changeOwner($placeBase, $commander->player);

					$commander->base = $placeBase;

					$this->commanderManager->endTravel($commander, Commander::AFFECTED);
					$commander->line = 2;

					$this->eventDispatcher->dispatch(new PlaceOwnerChangeEvent($place));

					// PATCH DEGUEU POUR LES MUTLIS-COMBATS
					$this->notificationManager->patchForMultiCombats($commander->player, $placeBase->player, $commander->getArrivalDate());
					// défaite
				} else {
					// TODO check if these instructions still have use
					/*$baseCommandersCount = count($baseCommanders);
					for ($i = 0; $i < $baseCommandersCount; ++$i) {
						if (Commander::DEAD == $place->commanders[$i]->statement) {
							unset($place->commanders[$i]);
							$place->commanders = array_merge($place->commanders);
						}
					}*/

					$this->placeManager->sendNotifForConquest($place, Place::CONQUERPLAYERWHITBATTLEFAIL, $commander, $reportIds);
				}
			} else {
				// si c'est la même couleur
				if ($place->base->player->faction->identifier === $commander->player->faction->identifier) {
					// si c'est une de nos planètes
					// on tente de se poser
					$this->commanderManager->uChangeBase($commander);
				} else {
					// si c'est une base alliée on repart
					($this->moveFleet)(
						commander: $commander,
						origin: $place,
						destination: $commander->startPlace,
						mission: CommanderMission::Back,
					);
					$this->placeManager->sendNotif($place, Place::CHANGELOST, $commander);
				}
			}

			// colonisation
		} else {
			// faire un combat
			LiveReport::$type = Commander::COLO;
			LiveReport::$dFight = $commander->getArrivalDate();
			LiveReport::$isLegal = true;

			$this->commanderManager->startFight($place, $commander);

			// victoire
			if (!$commander->isDead()) {
				// créer une base
				// TODO factorize in a service
				$ob = new OrbitalBase(
					id: Uuid::v4(),
					place: $place,
					player: $commander->player,
					name: 'colonie',
					// TODO transform into constant
					iSchool: 500,
					iAntiSpy: 500,
					resourcesStorage: 2000,
					createdAt: new \DateTimeImmutable(),
					updatedAt: new \DateTimeImmutable(),
				);
				$this->orbitalBasePointsHandler->updatePoints($ob);

				$this->orbitalBaseRepository->save($ob);

				// attibuer le commander à la place
				$commander->base = $ob;
				$this->commanderManager->endTravel($commander, Commander::AFFECTED);
				$commander->line = 2;

				// création du rapport
				$report = $this->commanderManager->createReport($place);

				$place->danger = 0;
				$place->base = $ob;

				$this->placeManager->sendNotif($place, Place::CONQUEREMPTYSSUCCESS, $commander, $report);

				$this->eventDispatcher->dispatch(new PlaceOwnerChangeEvent($place));

				// défaite
			} else {
				// création du rapport
				$report = $this->commanderManager->createReport($place);

				// mise à jour du danger
				// TODO Factorize in service
				$percentage = (($report->defenderPevAtEnd + 1) / ($report->defenderPevAtBeginning + 1)) * 100;
				$place->danger = round(($percentage * $place->danger) / 100);

				$this->placeManager->sendNotif($place, Place::CONQUEREMPTYFAIL, $commander);
			}
		}
		$this->entityManager->flush();
	}
}
