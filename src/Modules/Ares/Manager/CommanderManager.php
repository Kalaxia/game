<?php

declare(strict_types=1);

namespace App\Modules\Ares\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Ares\Application\Handler\Movement\MoveFleet;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Message\CommanderTravelMessage;
use App\Modules\Ares\Model\Commander;
use App\Modules\Artemis\Application\Handler\AntiSpyHandler;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Manager\PlaceManager;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use App\Shared\Application\SchedulerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class CommanderManager implements SchedulerInterface
{
	public function __construct(
		private DurationHandler $durationHandler,
		private AntiSpyHandler $antiSpyHandler,
		private EntityManagerInterface $entityManager,
		private CommanderRepositoryInterface $commanderRepository,
		private MoveFleet $moveFleet,
		private PlaceManager $placeManager,
		private MessageBusInterface $messageBus,
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

	/**
	 * Sends every ship of the fleet back to the hangar of its base, and dissolves its squadrons.
	 */
	public function emptySquadrons(Commander $commander): void
	{
		foreach ($commander->squadrons as $squadron) {
			$commander->base->addShips($squadron->shipCategory, $squadron->ships);
		}

		$commander->squadrons->clear();
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
		// @WARNING check if this is the right property to use, originally rplanetId to Place
		$commanderPlace = $commander->startPlace;
		// si la place et la flotte ont la même couleur
		// on pose la flotte si il y a assez de place
		// sinon on met la flotte dans les hangars
		// TODO replace with specification
		if (null === $place || $place->player->id !== $commander->player->id) {
			// retour forcé
			($this->moveFleet)(
				commander: $commander,
				origin: $place,
				destination: $commanderPlace,
				mission: CommanderMission::Back,
			);
			$this->placeManager->sendNotif($place, Planet::CHANGELOST, $commander);
			$this->entityManager->flush();

			return;
		}
		$commander->base = $place;
		$this->endTravel($commander, Commander::AFFECTED);

		$this->placeManager->sendNotif($place, Planet::CHANGESUCCESS, $commander);

		// modifier le rPlayer (ne se modifie pas si c'est le même)
		$commander->player = $place->player;

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
}
