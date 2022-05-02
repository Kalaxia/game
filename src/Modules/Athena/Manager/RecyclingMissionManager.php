<?php

/**
 * RecyclingMissionManager.
 *
 * @author Jacky Casas
 * @copyright Asylamba
 *
 * @version 09.02.15
 **/

namespace App\Modules\Athena\Manager;

use App\Classes\Entity\EntityManager;
use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Message\RecyclingMissionMessage;
use App\Modules\Athena\Model\RecyclingMission;
use App\Shared\Application\SchedulerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class RecyclingMissionManager implements SchedulerInterface
{
	public function __construct(
		protected EntityManager $entityManager,
		protected MessageBusInterface $messageBus,
	) {
	}

	public function schedule(): void
	{
		$missions = $this->entityManager->getRepository(RecyclingMission::class)->getAll();

		/** @var RecyclingMission $mission */
		foreach ($missions as $mission) {
			$this->messageBus->dispatch(new RecyclingMissionMessage($mission->id), [DateTimeConverter::to_delay_stamp($mission->uRecycling)]);
		}
	}

	public function get(int $id): ?RecyclingMission
	{
		return $this->entityManager->getRepository(RecyclingMission::class)->get($id);
	}

	public function getBaseMissions($baseId): array
	{
		return $this->entityManager->getRepository(RecyclingMission::class)->getBaseMissions($baseId);
	}

	/**
	 * @return list<RecyclingMission>
	 */
	public function getBaseActiveMissions(int $baseId): array
	{
		return $this->entityManager->getRepository(RecyclingMission::class)->getBaseActiveMissions($baseId);
	}

	public function add(RecyclingMission $recyclingMission): void
	{
		$this->entityManager->persist($recyclingMission);
		$this->entityManager->flush($recyclingMission);

		$this->messageBus->dispatch(
			new RecyclingMissionMessage($recyclingMission->id),
			[DateTimeConverter::to_delay_stamp($recyclingMission->uRecycling)]
		);
	}

	public function removeBaseMissions(int $baseId): void
	{
		// @TODO handle properly cancellations
		// foreach ($this->getBaseActiveMissions($baseId) as $mission) {
		// $this->realtimeActionScheduler->cancel($mission, $mission->uRecycling);
		// }
		$this->entityManager->getRepository(RecyclingMission::class)->removeBaseMissions($baseId);
	}
}
