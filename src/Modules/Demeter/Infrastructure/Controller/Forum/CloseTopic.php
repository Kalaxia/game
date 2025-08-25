<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Controller\Forum;

use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class CloseTopic extends AbstractController
{
	public function __invoke(
		ForumTopicRepositoryInterface $forumTopicRepository,
		Player $currentPlayer,
		int $forumId,
		Uuid $topicId,
	): Response {
		$topic = $forumTopicRepository->get($topicId)
			?? throw $this->createNotFoundException('Topic not found');
		
		if ($currentPlayer->status <= 2) {
			throw $this->createAccessDeniedException('You cannot close or open a topic');
		}

		$topic->isClosed = !$topic->isClosed;

		$forumTopicRepository->save($topic);

		$this->addFlash('success', 'Le sujet a bien été fermé/ouvert');

		return $this->redirectToRoute('view_forum_topic', [
			'forumId' => $forumId,
			'topicId' => $topicId,
		]);
	}
}
