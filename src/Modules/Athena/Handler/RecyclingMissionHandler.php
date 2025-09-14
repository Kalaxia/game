<?php

namespace App\Modules\Athena\Handler;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Athena\Domain\Repository\RecyclingLogRepositoryInterface;
use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Athena\Domain\Service\Recycling\ExtractPoints;
use App\Modules\Athena\Domain\Service\Recycling\GetMissionTime;
use App\Modules\Athena\Domain\Service\Recycling\RecycleCredits;
use App\Modules\Athena\Domain\Service\Recycling\RecycleResources;
use App\Modules\Athena\Domain\Service\Recycling\RecycleShips;
use App\Modules\Athena\Message\RecyclingMissionMessage;
use App\Modules\Athena\Model\RecyclingLog;
use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Galaxy\Domain\Enum\PlaceType;
use App\Modules\Galaxy\Manager\PlaceManager;
use App\Modules\Galaxy\Manager\PlanetManager;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Shared\Application\Handler\DurationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class RecyclingMissionHandler
{
	public function __construct(
		private DurationHandler                     $durationHandler,
		private EntityManagerInterface              $entityManager,
		private PlanetManager                       $planetManager,
		private PlaceManager                        $placeManager,
		private PlayerManager                       $playerManager,
		private GetMissionTime                      $getMissionTime,
		private NotificationRepositoryInterface     $notificationRepository,
		private RecyclingMissionRepositoryInterface $recyclingMissionRepository,
		private RecyclingLogRepositoryInterface     $recyclingLogRepository,
		private MessageBusInterface                 $messageBus,
		private ExtractPoints                       $extractPoints,
		private RecycleResources                    $recycleResources,
		private RecycleCredits                      $recycleCredits,
		private RecycleShips $recycleShips,
		private UrlGeneratorInterface $urlGenerator,
		private LoggerInterface $logger,
	) {
	}

	public function __invoke(RecyclingMissionMessage $message): void
	{
		$mission = $this->recyclingMissionRepository->get($message->getRecyclingMissionId());
		$planet = $mission->base;
		$targetPlace = $mission->target;

		$this->logger->debug('Processing recycling mission {missionId}. Initial target resources : {targetResources}', [
			'missionId' => $mission->id->toRfc4122(),
			'targetResources' => $targetPlace->resources,
		]);

		$player = $planet->player;

		if (null === $player || !$player->isAlive()) {
			$mission->stop();

			$this->recyclingMissionRepository->save($mission);

			return;
		}

		if (PlaceType::Empty === $targetPlace->getType()) {
			$this->logger->debug('Mission {missionId} target has become empty',[
				'missionId' => $mission->id->toRfc4122(),
			]);
			// stop the mission
			$mission->statement = RecyclingMission::ST_DELETED;

			$this->notificationRepository->save(NotificationBuilder::new()
				->setTitle('Arrêt de mission de recyclage')
				->setContent(NotificationBuilder::paragraph(
					'Un ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('map', ['place' => $mission->target->id]),
						'lieu',
					),
					' que vous recycliez est désormais totalement dépourvu de ressources et s\'est donc transformé en lieu vide.',
					NotificationBuilder::divider(),
					'Vos recycleurs restent donc stationnés sur votre ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('map', ['place' => $mission->base->id]),
						'base orbitale',
					),
					' le temps que vous programmiez une autre mission.',
				))
				->for($player));

			$this->entityManager->flush();

			return;
		}
		// make the recycling : decrease resources on the target place
		$totalRecycled = ($this->extractPoints)($mission);
		$targetPlace->resources -= $totalRecycled;
		// if there is no more resource
		if ($targetPlace->resources <= 0) {
			// Avoid out of range errors
			$targetPlace->resources = 0;
			// the place become an empty place
			$this->placeManager->turnAsEmptyPlace($targetPlace);

			// stop the mission
			$mission->statement = RecyclingMission::ST_DELETED;

			// send notification to the player
			$notification = NotificationBuilder::new()
				->setTitle('Arrêt de mission de recyclage')
				->setContent(NotificationBuilder::paragraph(
					'Un ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('map', ['place' => $mission->target->id]),
						'lieu',
					),
					' que vous recycliez est désormais totalement dépourvu de ressources et s\'est donc transformé en lieu vide.',
					NotificationBuilder::divider(),
					'Vos recycleurs restent donc stationnés sur votre ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('map', ['place' => $planet->id]),
						'base orbitale',
					),
					' le temps que vous programmiez une autre mission.',
				))
				->for($player);

			$this->notificationRepository->save($notification);
		}

		// if the sector change its color between 2 recyclings
		// TODO transform into specification and factorize
		if (null !== $targetPlace->system->sector->faction && !$player->faction->id->equals($targetPlace->system->sector->faction?->id)) {
			$mission->stop();

			$this->notificationRepository->save(NotificationBuilder::new()
				->setTitle('Arrêt de mission de recyclage')
				->setContent(NotificationBuilder::paragraph(
					'Le secteur d\'un ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('map', ['place' => $mission->target->id]),
						'lieu',
					),
					' que vous recycliez est passé à l\'ennemi, vous ne pouvez donc plus y envoyer vos recycleurs. La mission est annulée.',
					NotificationBuilder::divider(),
					'Vos recycleurs restent donc stationnés sur votre ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('map', ['place' => $mission->base->id]),
						'base orbitale',
					),
					' le temps que vous programmiez une autre mission.',
				))
				->for($player));
		}

		// diversify a little (resource and credit)
		$balancingPercent = random_int(-5, 5);

		$buyShip = ($this->recycleShips)($mission, $totalRecycled);
		$resourceRecycled = ($this->recycleResources)($mission, $totalRecycled, $balancingPercent);
		$creditRecycled = ($this->recycleCredits)($mission, $totalRecycled, $balancingPercent);

		// create a RecyclingLog
		$rl = new RecyclingLog(
			id: Uuid::v4(),
			mission: $mission,
			createdAt: new \DateTimeImmutable(),
			resources: $resourceRecycled,
			credits: $creditRecycled,
			ship0: $buyShip[0],
			ship1: $buyShip[1],
			ship2: $buyShip[2],
			ship3: $buyShip[3],
			ship4: $buyShip[4],
			ship5: $buyShip[5],
			ship6: $buyShip[6],
			ship7: $buyShip[7],
			ship8: $buyShip[8],
			ship9: $buyShip[9],
			ship10: $buyShip[10],
			ship11: $buyShip[11],
		);

		$this->recyclingLogRepository->save($rl);

		// give to the planet ($planet) and player what was recycled
		foreach (ShipCategory::cases() as $shipCategory) {
			$planet->addShips($shipCategory, $buyShip[$shipCategory->value]);
		}
		$this->playerManager->increaseCredit($player, $creditRecycled);

		// add recyclers waiting to the mission
		$mission->recyclerQuantity += $mission->addToNextMission;
		$mission->addToNextMission = 0;

		// if a mission is stopped by the user, delete it
		if ($mission->isBeingDeleted()) {
			$mission->statement = RecyclingMission::ST_DELETED;
		}

		$this->logger->debug('Recycling mission {missionId} has been processed. Final target resources : {targetResources}', [
			'missionId' => $mission->id->toRfc4122(),
			'targetResources' => $targetPlace->resources,
			'refinedCredits' => $creditRecycled,
			'refinedResources' => $resourceRecycled,
			'refinedShips' => $buyShip,
		]);

		// update the cycle time in case the time mode has changed or new bonuses apply since the previous occurrence
		$mission->cycleTime = ($this->getMissionTime)($planet, $targetPlace, $player);
		$mission->endedAt = $this->durationHandler->getDurationEnd($mission->endedAt, $mission->cycleTime);
		// Schedule the next mission if there is still resources
		if (!$mission->isDeleted()) {
			$this->messageBus->dispatch(
				new RecyclingMissionMessage($mission->id),
				[DateTimeConverter::to_delay_stamp($mission->endedAt)]
			);
		}
		$this->entityManager->flush();
	}
}
