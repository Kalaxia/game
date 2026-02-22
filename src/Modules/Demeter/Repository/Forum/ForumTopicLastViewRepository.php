<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Forum;

use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicLastViewRepositoryInterface;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Demeter\Model\Forum\ForumTopicLastView;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;

class ForumTopicLastViewRepository extends DoctrineRepository implements ForumTopicLastViewRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, ForumTopicLastView::class);
	}

	public function getByTopicAndPlayer(ForumTopic $forumTopic, Player $player): ?ForumTopicLastView
	{
		return $this->findOneBy([
			'forumTopic' => $forumTopic,
			'player' => $player,
		]);
	}
}
