<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Application\Persister;

use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;

final readonly class NotificationPersister
{
	public function __construct(
		private NotificationRepositoryInterface $notificationRepository,
		private PlayerRepositoryInterface $playerRepository,
	) {
	}

	public function saveFromBuilder(NotificationBuilder $builder): void
	{
		$recipientSpecification = $builder->getRecipientSpecification();
		if (null !== $recipientSpecification) {
			$builder->forPlayers($this->playerRepository->getBySpecification($recipientSpecification));
		}

		foreach ($builder->build() as $notification) {
			$this->notificationRepository->save($notification);
		}
	}
}
