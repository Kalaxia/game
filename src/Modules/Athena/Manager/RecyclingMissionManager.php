<?php

namespace App\Modules\Athena\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Athena\Message\RecyclingMissionMessage;
use App\Modules\Athena\Model\RecyclingMission;
use App\Shared\Application\SchedulerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class RecyclingMissionManager implements SchedulerInterface
{
	public function __construct(
		private readonly RecyclingMissionRepositoryInterface $recyclingMissionRepository,
		private readonly MessageBusInterface $messageBus,
	) {
	}

	public function schedule(): void
	{
		$missions = $this->recyclingMissionRepository->getAll();

		foreach ($missions as $mission) {
			$this->messageBus->dispatch(
				new RecyclingMissionMessage($mission->id),
				[DateTimeConverter::to_delay_stamp($mission->endedAt)],
			);
		}
	}

	public function add(RecyclingMission $recyclingMission): void
	{
		$this->recyclingMissionRepository->save($recyclingMission);

		$this->messageBus->dispatch(
			new RecyclingMissionMessage($recyclingMission->id),
			[DateTimeConverter::to_delay_stamp($recyclingMission->endedAt)]
		);
	}
}
