<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Application\EventListener;

use App\Modules\Demeter\Domain\Event\NewLeaderEvent;
use App\Modules\Hermes\Domain\Event\NotificationEvent;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(NewLeaderEvent::class, priority: -100)]
readonly class NotificationListener
{
	public function __construct(
		private NotificationRepositoryInterface $notificationRepository,
		private LoggerInterface $logger,
		private PlayerRepositoryInterface $playerRepository,
	) {
	}

	public function __invoke(NotificationEvent $event): void
	{
		$notificationBuilder = $event->getNotificationBuilder();

		$players = $event->getNotificationRecipients();

		if (null !== ($specification = $event->getNotificationRecipientsSpecification())) {
			$players = $this->playerRepository->getBySpecification($specification);
		}

		$this->logger->debug('Sending notification to {count} players for event {eventClass}', [
			'count' => count($players),
			'eventClass' => basename(get_class($event)),
		]);

		foreach ($players as $player) {
			$notification = $notificationBuilder->for($player);

			$this->notificationRepository->save($notification);
		}
	}
}
