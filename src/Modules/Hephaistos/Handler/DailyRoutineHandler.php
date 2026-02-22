<?php

namespace App\Modules\Hephaistos\Handler;

use App\Modules\Hephaistos\Message\DailyRoutineMessage;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerFinancialReportRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class DailyRoutineHandler
{
	public function __construct(
		private ClockInterface $clock,
		private PlayerManager $playerManager,
		private DurationHandler $durationHandler,
		private PlayerRepositoryInterface $playerRepository,
		private NotificationRepositoryInterface $notificationRepository,
		private PlayerFinancialReportRepositoryInterface $playerFinancialReportRepository,
		#[Autowire('%zeus.player.inactive_time_limit%')]
		private int $playerInactiveTimeLimit,
		#[Autowire('%zeus.player.global_inactive_time%')]
		private int $playerGlobalInactiveTime,
		#[Autowire('%hermes.notifications.timeout.read%')]
		private int $notificationsReadTimeout,
		#[Autowire('%hermes.notifications.timeout.unread%')]
		private int $notificationsUnreadTimeout,
		#[Autowire('%zeus.player_financial_reports.timeout%')]
		private int $playerFinancialReportsTimeout,
	) {
	}

	public function __invoke(DailyRoutineMessage $message): void
	{
		$this->notificationRepository->cleanNotifications(
			$this->notificationsReadTimeout,
			$this->notificationsUnreadTimeout
		);

		$this->playerFinancialReportRepository->cleanPlayerFinancialReports($this->playerFinancialReportsTimeout);

		$players = $this->playerRepository->getByStatements([Player::ACTIVE, Player::INACTIVE]);
		$nbPlayers = count($players);
		// @TODO understand this strange loop condition
		for ($i = $nbPlayers - 1; $i >= 0; --$i) {
			$player = $players[$i];
			$hoursSinceLastConnection = $this->durationHandler->getHoursDiff($player->dLastConnection, $this->clock->now());
			if ($hoursSinceLastConnection >= $this->playerInactiveTimeLimit) {
				$this->playerManager->kill($player);
			} elseif ($hoursSinceLastConnection >= $this->playerGlobalInactiveTime && Player::ACTIVE === $player->statement) {
				$player->statement = Player::INACTIVE;
				$this->playerRepository->save($player);

				//				if ('enabled' === $this->apiMode) {
				//					// sending email API call
				//					// TODO Modify this behavior
				//					// $this->api->sendMail($player->bind, API::TEMPLATE_INACTIVE_PLAYER);
				//				}
			}
		}
	}
}
