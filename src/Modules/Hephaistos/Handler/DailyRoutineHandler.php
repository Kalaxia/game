<?php

namespace App\Modules\Hephaistos\Handler;

use App\Classes\Library\Utils;
use App\Classes\Worker\API;
use App\Modules\Hephaistos\Message\DailyRoutineMessage;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class DailyRoutineHandler
{
	public function __construct(
		private PlayerManager             $playerManager,
		private DurationHandler $durationHandler,
		private PlayerRepositoryInterface $playerRepository,
		private NotificationRepositoryInterface $notificationRepository,
		private int                       $playerInactiveTimeLimit,
		private int                       $playerGlobalInactiveTime,
		private int                       $notificationsReadTimeout,
		private int                       $notificationsUnreadTimeout
	) {
	}

	public function __invoke(DailyRoutineMessage $message): void
	{
		$this->notificationRepository->cleanNotifications(
			$this->notificationsReadTimeout,
			$this->notificationsUnreadTimeout
		);

		$players = $this->playerRepository->getByStatements([Player::ACTIVE, Player::INACTIVE]);
		$nbPlayers = count($players);
		// @TODO understand this strange loop condition
		for ($i = $nbPlayers - 1; $i >= 0; --$i) {
			$player = $players[$i];
			$hoursSinceLastConnection = $this->durationHandler->getHoursDiff($player->dLastConnection, new \DateTimeImmutable());
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
