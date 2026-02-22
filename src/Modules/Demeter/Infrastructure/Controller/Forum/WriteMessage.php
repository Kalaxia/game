<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Controller\Forum;

use App\Classes\Library\Parser;
use App\Modules\Demeter\Domain\Repository\Forum\ForumMessageRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicRepositoryInterface;
use App\Modules\Demeter\Model\Forum\ForumMessage;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class WriteMessage extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ForumTopicRepositoryInterface $forumTopicRepository,
		ForumMessageRepositoryInterface $forumMessageRepository,
		PlayerRepositoryInterface $playerRepository,
		Parser $parser,
		int $forumId,
		Uuid $topicId,
	): Response {
		$topic = $forumTopicRepository->get($topicId)
			?? throw $this->createNotFoundException('Topic not found');

		if ($topic->isClosed) {
			throw new ConflictHttpException('Ce sujet est fermé.');
		}

		$content = $request->request->get('content');

		$parser->parseBigTag = true;

		$pContent = $parser->parse($content);
		$message = new ForumMessage(
			id: Uuid::v4(),
			topic: $topic,
			player: $currentPlayer,
			oContent: $content,
			pContent: $pContent,
			statement: ForumMessage::PUBLISHED,
			createdAt: new \DateTimeImmutable(),
			updatedAt: new \DateTimeImmutable(),
		);

		$forumMessageRepository->save($message);

		++$topic->messagesCount;
		$topic->lastContributedAt = new \DateTimeImmutable();

		$forumTopicRepository->save($topic);
		// tutorial
		// TODO Move to event
		if (!$currentPlayer->stepDone && TutorialResource::FACTION_FORUM === $currentPlayer->stepTutorial) {
			$currentPlayer->stepDone = true;

			$playerRepository->save($currentPlayer);
		}

		$this->addFlash('success', 'Message créé.');

		return $this->redirectToRoute('view_forum_topic', [
			'forumId' => $forumId,
			'topicId' => $topicId,
		]);
	}
}
