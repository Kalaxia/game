<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Infrastructure\Command;

use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
	name: 'app:hermes:clean-notifications',
	description: 'Manually trigger notifications cleaning query',
)]
class CleanNotifications extends Command
{
	public function __construct(
		private readonly NotificationRepositoryInterface $notificationRepository,
		#[Autowire('%hermes.notifications.timeout.read%')]
		private readonly int $notificationsReadTimeout,
		#[Autowire('%hermes.notifications.timeout.unread%')]
		private readonly int $notificationsUnreadTimeout,
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);

		$style->info(sprintf(
			'Cleaning notifications. Read timeout : %d days, Unread timeout : %d days',
			$this->notificationsReadTimeout,
			$this->notificationsUnreadTimeout,
		));

		$this->notificationRepository->cleanNotifications(
			$this->notificationsReadTimeout,
			$this->notificationsUnreadTimeout,
		);

		$style->success('Successfully cleaned notifications.');

		return static::SUCCESS;
	}
}
