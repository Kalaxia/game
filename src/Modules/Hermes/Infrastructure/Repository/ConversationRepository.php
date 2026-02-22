<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Infrastructure\Repository;

use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class ConversationRepository extends DoctrineRepository implements ConversationRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Conversation::class);
	}

	public function getOneByPlayer(Player $player): Conversation
	{
		$qb = $this->createQueryBuilder('c');

		$qb
			->leftJoin('c.players', 'cu')
			->where('cu.player = :player')
			->setParameter('player', $player);

		return $qb->getQuery()->getSingleResult();
	}

	public function getOne(Uuid $id): ?Conversation
	{
		return $this->find($id);
	}

	public function countPlayerUnreadConversations(Player $player): int
	{
		$qb = $this->createQueryBuilder('c');

		$qb
			->select('COUNT(c.id)')
			->leftJoin('c.players', 'cu')
			->where('cu.player = :player')
			->andWhere($qb->expr()->orX(
				$qb->expr()->isNull('cu.lastViewedAt'),
				$qb->expr()->lt('cu.lastViewedAt', 'c.lastMessageAt'),
			))
			->setParameter('player', $player);

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getPlayerConversations(Player $player, int $mode, int $page = 1): array
	{
		$qb = $this->createQueryBuilder('c');

		$qb
			->leftJoin('c.players', 'cu')
			->where('cu.player = :player')
			->andWhere('cu.conversationStatus = :mode')
			->orderBy('c.lastMessageAt', 'DESC')
			->setFirstResult(($page - 1) * Conversation::CONVERSATION_BY_PAGE)
			->setMaxResults(Conversation::CONVERSATION_BY_PAGE)
			->setParameter('player', $player)
			->setParameter('mode', $mode);

		return $qb->getQuery()->getResult();
	}
}
