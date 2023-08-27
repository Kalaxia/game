<?php

declare(strict_types=1);

namespace App\Modules\Ares\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Application\Handler\VirtualCommanderHandler;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Message\CommanderTravelMessage;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\LiveReport;
use App\Modules\Ares\Model\Report;
use App\Modules\Artemis\Application\Handler\AntiSpyHandler;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Application\Handler\GetTravelTime;
use App\Modules\Gaia\Domain\Model\TravelType;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Model\Place;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;
use App\Shared\Application\Handler\DurationHandler;
use App\Shared\Application\SchedulerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class CommanderManager implements SchedulerInterface
{
	public function __construct(
		private DurationHandler $durationHandler,
		private AntiSpyHandler $antiSpyHandler,
		private GetTravelTime $getTravelTime,
		private EntityManagerInterface $entityManager,
		private CommanderRepositoryInterface $commanderRepository,
		private ReportRepositoryInterface $reportRepository,
		private PlayerBonusManager $playerBonusManager,
		private PlaceManager $placeManager,
		private MessageBusInterface $messageBus,
		private FightManager $fightManager,
		private VirtualCommanderHandler $virtualCommanderHandler,
		private CommanderArmyHandler $commanderArmyHandler,
	) {
	}

	public function getVisibleIncomingAttacks(Player $player): array
	{
		$attackingCommanders = $this->commanderRepository->getIncomingAttacks($player);
		$incomingCommanders = [];

		foreach ($attackingCommanders as $commander) {
			// va chercher les heures auxquelles il rentre dans les cercles d'espionnage
			$times = $this->antiSpyHandler->getAntiSpyEntryTime(
				$commander->startPlace,
				$commander->destinationPlace,
				$commander,
			);

			// TODO WARNING I suppose that true values may mean that the attack is already detected but not sure
			if (true === $times[0] || new \DateTimeImmutable() >= $times[0]) {
				$incomingCommanders[] = $commander;
			}
		}

		return $incomingCommanders;
	}

	public function schedule(): void
	{
		$commanders = $this->commanderRepository->getMovingCommanders();

		foreach ($commanders as $commander) {
			$this->messageBus->dispatch(
				new CommanderTravelMessage($commander->id),
				[DateTimeConverter::to_delay_stamp($commander->getArrivalDate())],
			);
		}
	}

	public function emptySquadrons(Commander $commander): void
	{
		$orbitalBase = $commander->base;

		$nbSquadrons = count($commander->squadronsIds);
		for ($i = 0; $i < $nbSquadrons; ++$i) {
			for ($j = 0; $j < 12; ++$j) {
				$orbitalBase->addShips($j, $commander->getSquadron($i)->getShipQuantity($j));
			}
			$commander->getSquadron($i)->emptySquadron();
		}
	}

	public function move(
		Commander $commander,
		Place $rDestinationPlace,
		Place $rStartPlace,
		int $travelType,
		int $duration,
	): void {
		$commander->destinationPlace = $rDestinationPlace;
		$commander->startPlace = $rStartPlace;
		$commander->travelType = $travelType;
		$commander->statement = Commander::MOVING;

		$commander->departedAt = (3 != $travelType) ? new \DateTimeImmutable() : $commander->getArrivalDate();
		$date = \DateTime::createFromImmutable($commander->getDepartureDate());
		$date->modify(sprintf('+%d seconds', $duration));
		$commander->arrivedAt = \DateTimeImmutable::createFromMutable($date);

		$this->messageBus->dispatch(
			new CommanderTravelMessage($commander->id),
			[DateTimeConverter::to_delay_stamp($commander->getArrivalDate())],
		);
	}

	public function getPosition(Commander $commander, $x1, $y1, $x2, $y2): array
	{
		$x = $x1;
		$y = $y1;
		if ($commander->isMoving()) {
			$parcouredTime = $this->durationHandler->getDiff($commander->departedAt, new \DateTimeImmutable());
			$totalTime = $this->durationHandler->getDiff($commander->departedAt, $commander->arrivedAt);
			$progression = $parcouredTime / $totalTime;

			$x = $x1 + $progression * ($x2 - $x1);
			$y = $y1 + $progression * ($y2 - $y1);
		}

		return [$x, $y];
	}

	public function uChangeBase(Commander $commander): void
	{
		$place = $commander->destinationPlace;
		$base = $place->base;
		$placeCommanders = $this->commanderRepository->getBaseCommanders($base);
		// @WARNING check if this is the right property to use, originally rbaseId to Place
		$commanderPlace = $commander->startPlace;
		$player = $commander->player;
		$playerBonus = $this->playerBonusManager->getBonusByPlayer($player);
		// si la place et la flotte ont la même couleur
		// on pose la flotte si il y a assez de place
		// sinon on met la flotte dans les hangars
		// TODO replace with specification
		if ($place->player->id !== $commander->player->id || Place::TYP_ORBITALBASE !== $place->typeOfPlace) {
			// retour forcé
			$this->comeBack($place, $commander, $commanderPlace, $playerBonus);
			$this->placeManager->sendNotif($place, Place::CHANGELOST, $commander);
			$this->entityManager->flush();

			return;
		}
		$maxCom =
			($place->base->isMilitaryBase() || $place->base->isCapital())
			? OrbitalBase::MAXCOMMANDERMILITARY
			: OrbitalBase::MAXCOMMANDERSTANDARD
		;

		// si place a assez de case libre :
		if (count($placeCommanders) < $maxCom) {
			$comLine2 = 0;

			foreach ($placeCommanders as $com) {
				if (2 == $com->line) {
					++$comLine2;
				}
			}

			if (OrbitalBase::MAXCOMMANDERMILITARY == $maxCom) {
				if ($comLine2 < 2) {
					$commander->line = 2;
				} else {
					$commander->line = 1;
				}
			} else {
				if ($comLine2 < 1) {
					$commander->line = 2;
				} else {
					$commander->line = 1;
				}
			}

			// changer rBase commander
			// @TODO update that
			$commander->base = $base;
			$this->endTravel($commander, Commander::AFFECTED);

			// envoi de notif
			$this->placeManager->sendNotif($place, Place::CHANGESUCCESS, $commander);
		} else {
			// changer rBase commander
			$commander->base = $base;
			$this->endTravel($commander, Commander::RESERVE);

			$this->emptySquadrons($commander);

			// envoi de notif
			$this->placeManager->sendNotif($place, Place::CHANGEFAIL, $commander);
		}

		// modifier le rPlayer (ne se modifie pas si c'est le même)
		$commander->player = $base->player;

		$this->entityManager->flush();
	}

	public function endTravel(Commander $commander, int $statement): void
	{
		$commander->travelType = null;
		$commander->departedAt = null;
		$commander->arrivedAt = null;
		$commander->startPlace = null;
		$commander->destinationPlace = null;
		$commander->statement = $statement;
	}

	// HELPER

	// comeBack
	public function comeBack(Place $place, Commander $commander, Place $commanderPlace, $playerBonus): void
	{
		$duration = ($this->getTravelTime)($commanderPlace, $place, TravelType::Fleet, $playerBonus);

		$this->move($commander, $commander->base->place, $place, Commander::BACK, $duration);
	}

	public function lootAnEmptyPlace(Place $place, Commander $commander, PlayerBonus $playerBonus): void
	{
		$bonus = $playerBonus->bonuses->get(PlayerBonusId::SHIP_CONTAINER);

		$storage = $this->commanderArmyHandler->getPevToLoot($commander) * Commander::COEFFLOOT;
		$storage += round($storage * ((2 * $bonus) / 100));

		$resourcesLooted = ($storage > $place->resources) ? $place->resources : $storage;

		$place->resources -= $resourcesLooted;
		$commander->resources = $resourcesLooted;

		LiveReport::$resources = $resourcesLooted;
	}

	public function startFight(Place $place, Commander $commander, Commander $enemyCommander = null): void
	{
		if (null === $enemyCommander) {
			$enemyCommander = $this->virtualCommanderHandler->createVirtualCommander($place);
		}
		$this->fightManager->startFight($commander, $enemyCommander);
	}

	public function createReport(Place $place): Report
	{
		$report = Report::fromLiveReport($place);

		$this->reportRepository->save($report);
		LiveReport::clear();

		return $report;
	}
}
