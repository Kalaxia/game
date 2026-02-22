<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Repository\Forum;

use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Demeter\Model\Forum\ForumTopicLastView;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;

/**
 * @extends EntityRepositoryInterface<ForumTopicLastView>
 */
interface ForumTopicLastViewRepositoryInterface extends EntityRepositoryInterface
{
	public function getByTopicAndPlayer(ForumTopic $forumTopic, Player $player): ?ForumTopicLastView;
}
