<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Repository\Forum;

use App\Modules\Demeter\Model\Forum\ForumMessage;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<ForumMessage>
 */
interface ForumMessageRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?ForumMessage;

	/**
	 * @return list<ForumMessage>
	 */
	public function getTopicMessages(ForumTopic $topic, int $limit = 20, int $offset = 0): array;
}
