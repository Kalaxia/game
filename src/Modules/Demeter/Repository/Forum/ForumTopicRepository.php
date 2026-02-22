<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Forum;

use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Demeter\Model\Forum\ForumTopicLastView;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

class ForumTopicRepository extends DoctrineRepository implements ForumTopicRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, ForumTopic::class);
	}

	public function get(Uuid $id): ?ForumTopic
	{
		return $this->find($id);
	}

	public function getByForumAndPlayer(int $forum, Player $player): ?ForumTopic
	{
		return $this->findOneBy(
			[
				'forum' => $forum,
				'player' => $player,
			],
			[
				'createdAt' => 'DESC',
			],
		);
	}

	public function getByForumAndFaction(int $forum, Color $faction, int $limit = 10, int $offset = 0, bool $archived = false): array
	{
		return $this->findBy(
			[
				'forum' => $forum,
				'faction' => $faction,
				'isArchived' => $archived,
			],
			[
				'isUp' => 'DESC',
				'lastContributedAt' => 'DESC',
			],
			$limit,
			$offset,
		);
	}

	public function getByForumAndFactionWithLastViews(int $forum, Color $faction, Player $player, int $limit = 10, int $offset = 0, bool $archived = false): array
	{
		$qb = $this->createQueryBuilder('t');

		$qb
			->select('t', 'lv')
			->leftJoin(ForumTopicLastView::class, 'lv', Join::WITH, 'lv.forumTopic = t AND lv.player = :player')
			->where('t.forum = :forum')
			->andWhere('t.faction = :faction')
			->setParameter('player', $player->id)
			->setParameter('faction', $faction->id, UuidType::NAME)
			->setParameter('forum', $forum)
		;

		return array_map(
			fn (array $chunk) => [
				'topic' => $chunk[0],
				'last_view' => $chunk[1],
			],
			array_chunk($qb->getQuery()->getResult(), 2)
		);
	}

	public function getByForum(int $forum, int $limit = 10, int $offset = 0, bool $archived = false): array
	{
		return $this->findBy(
			[
				'forum' => $forum,
				'isArchived' => $archived,
			],
			[
				'isUp' => 'DESC',
				'lastContributedAt' => 'DESC',
			],
			$limit,
			$offset,
		);
	}

	public function getByForumWithLastViews(int $forum, Player $player, int $limit = 10, int $offset = 0, bool $archived = false): array
	{
		$qb = $this->createQueryBuilder('t');

		$qb
			->select('t', 'lv')
			->leftJoin(ForumTopicLastView::class, 'lv', Join::WITH, 'lv.forumTopic = t AND lv.player = :player')
			->where('t.forum = :forum')
			->setParameter('player', $player->id)
			->setParameter('forum', $forum)
		;

		return array_map(
			fn (array $chunk) => [
				'topic' => $chunk[0],
				'last_view' => $chunk[1],
			],
			array_chunk($qb->getQuery()->getResult(), 2)
		);
	}
}
