<?php

namespace App\Modules\Hermes\Manager;

use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Model\Player;

readonly class NotificationManager
{
	public function __construct(private NotificationRepositoryInterface $notificationRepository)
	{
	}

	public function patchForMultiCombats(
		Player $commanderPlayer,
		Player $placePlayer,
		\DateTimeImmutable $arrivedAt,
	): void {
		$notifications = $this->notificationRepository->getMultiCombatNotifications(
			$commanderPlayer,
			$placePlayer,
			$arrivedAt,
		);
		$nbNotifications = count($notifications);
		if ($nbNotifications > 2) {
			for ($i = 0; $i < $nbNotifications - 2; ++$i) {
				$this->notificationRepository->remove($notifications[$i]);
			}
		}
	}
}
