<?php

namespace App\Modules\Demeter\Domain\Repository\Forum;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Demeter\Model\Forum\ForumTopicLastView;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<ForumTopic>
 */
interface ForumTopicRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?ForumTopic;

	public function getByForumAndPlayer(int $forum, Player $player): ?ForumTopic;

	/**
	 * @return list<ForumTopic>
	 */
	public function getByForumAndFaction(int $forum, Color $faction, int $limit = 10, int $offset = 0, bool $archived = false): array;

	/**
	 * @return list<array{
	 *     topic: ForumTopic,
	 *     last_view: ForumTopicLastView,
	 * }>
	 */
	public function getByForumAndFactionWithLastViews(int $forum, Color $faction, Player $player, int $limit = 10, int $offset = 0, bool $archived = false): array;

	/**
	 * @return list<ForumTopic>
	 */
	public function getByForum(int $forum, int $limit = 10, int $offset = 0, bool $archived = false): array;

	/**
	 * @return list<array{
	 *     topic: ForumTopic,
	 *     last_view: ForumTopicLastView,
	 * }>
	 */
	public function getByForumWithLastViews(int $forum, Player $player, int $limit = 10, int $offset = 0, bool $archived = false): array;
}
