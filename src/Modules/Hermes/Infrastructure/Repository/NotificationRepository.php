<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Infrastructure\Repository;

use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class NotificationRepository extends DoctrineRepository implements NotificationRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Notification::class);
	}

	public function get(Uuid $id): ?Notification
	{
		return $this->find($id);
	}

	public function getUnreadNotifications(Player $player, ?int $limit = null): array
	{
		$qb = $this->createQueryBuilder('n');

		$qb
			->where('n.player = :player')
			->andWhere('n.read = false')
			->addOrderBy('n.sentAt', 'DESC')
			->setParameter('player', $player);

		if (null !== $limit) {
			$qb->setMaxResults($limit);
		}

		return $qb->getQuery()->getResult();
	}

	public function countUnreadNotifications(Player $player): int
	{
		$qb = $this->createQueryBuilder('n');

		$qb
			->select('COUNT(n.id)')
			->where('n.player = :player')
			->andWhere('n.read = false')
			->setParameter('player', $player);

		return (int) $qb->getQuery()->getSingleScalarResult();
	}

	public function getPlayerNotificationsByArchive(Player $player, bool $isArchived): array
	{
		$qb = $this->createQueryBuilder('n');

		$qb
			->where('n.player = :player')
			->andWhere('n.archived = :is_archived')
			->addOrderBy('n.sentAt', 'DESC')
			->setParameter('player', $player)
			->setParameter('is_archived', $isArchived)
			->setMaxResults(60);

		return $qb->getQuery()->getResult();
	}

	public function getMultiCombatNotifications(
		Player $commanderPlayer,
		Player $placePlayer,
		\DateTimeImmutable $arrivedAt,
	): array {
		$qb = $this->createQueryBuilder('n');

		$qb
			->where($qb->expr()->orX(
				$qb->expr()->eq('n.player', ':commander_player'),
				$qb->expr()->eq('n.player', ':place_player'),
			))
			->andWhere('n.sentAt = :arrived_at')
			->setParameter('commander_player', $commanderPlayer)
			->setParameter('place_player', $placePlayer)
			->setParameter('arrived_at', $arrivedAt);

		return $qb->getQuery()->getResult();
	}

	public function removePlayerNotifications(Player $player): int
	{
		$qb = $this->createQueryBuilder('n');

		$qb
			->delete()
			->where('n.player = :player')
			->andWhere('n.archived = false')
			->setParameter('player', $player);

		return $qb->getQuery()->getResult();
	}

	public function cleanNotifications(int $readTimeout, int $unreadTimeout): int
	{
		$qb = $this->createQueryBuilder('n');

		$qb
			->delete()
			->where($qb->expr()->orX(
				$qb->expr()->andX(
					$qb->expr()->eq('n.read', 0),
					$qb->expr()->gt('DATE_DIFF(CURRENT_DATE(), n.sentAt)', ':unread_timeout')
				),
				$qb->expr()->andX(
					$qb->expr()->eq('n.read', 1),
					$qb->expr()->gt('DATE_DIFF(CURRENT_DATE(), n.sentAt)', ':read_timeout')
				)
			))
			->setParameter('unread_timeout', $unreadTimeout)
			->setParameter('read_timeout', $readTimeout);

		return $qb->getQuery()->getResult();
	}
}
