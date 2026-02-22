<?php

namespace App\Modules\Hermes\Domain\Repository;

use App\Modules\Hermes\Model\Notification;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Notification>
 */
interface NotificationRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?Notification;

	/**
	 * @return list<Notification>
	 */
	public function getUnreadNotifications(Player $player, ?int $limit = null): array;

	public function countUnreadNotifications(Player $player): int;

	/**
	 * @return list<Notification>
	 */
	public function getPlayerNotificationsByArchive(Player $player, bool $isArchived): array;

	/**
	 * @return list<Notification>
	 */
	public function getMultiCombatNotifications(
		Player $commanderPlayer,
		Player $placePlayer,
		\DateTimeImmutable $arrivedAt,
	): array;

	public function removePlayerNotifications(Player $player): int;

	public function cleanNotifications(int $readTimeout, int $unreadTimeout): int;
}
