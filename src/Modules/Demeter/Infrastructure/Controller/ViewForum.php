<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicRepositoryInterface;
use App\Modules\Demeter\Resource\ForumResources;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewForum extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ForumTopicRepositoryInterface $forumTopicRepository,
		?int $forumId,
	): Response {
		$topicsByForum = [];

		if (null === $forumId) {
			$showHome = true;
			$isStandard_topics = false;
			// page d'accueil des forums
			// charge les x premiers sujets de chaque forum
			for ($i = 1; $i <= ForumResources::size(); ++$i) {
				$forumId = ForumResources::getInfo($i, 'id');
				// TODO move this to a Voter
				if ($this->canViewForum($currentPlayer, $forumId)) {
					$topicsByForum[$forumId] = ($forumId < 20)
						? $forumTopicRepository->getByForumAndFactionWithLastViews($forumId, $currentPlayer->faction, $currentPlayer)
						: $forumTopicRepository->getByForumWithLastViews($forumId, $currentPlayer);
				}
			}
		} else {
			$showHome = false;
			$selectedForumId = $forumId;
			$archivedMode = 'archived' === $request->query->get('mode') && $currentPlayer->isGovernmentMember();
			// TODO move this to a Voter
			if ($this->canViewForum($currentPlayer, $forumId)) {
				$topicsByForum[$forumId] = ($forumId < 20)
					? $forumTopicRepository->getByForumAndFactionWithLastViews($forumId, $currentPlayer->faction, $currentPlayer, archived: $archivedMode)
					: $forumTopicRepository->getByForumWithLastViews($forumId, $currentPlayer, archived: $archivedMode);

				$isStandard_topics = true;
			} else {
				throw $this->createAccessDeniedException('You cannot access this forum');
			}
		}

		return $this->render('pages/demeter/faction/forum.html.twig', [
			'faction' => $currentPlayer->faction,
			'topics_by_forum' => $topicsByForum,
			'selected_forum_id' => $selectedForumId ?? null,
			'show_home' => $showHome,
			'display_standard_topics' => $isStandard_topics,
			'is_archived' => $archivedMode ?? false,
			'forum_ids' => array_filter(
				array_map(
					fn (int $index) => ForumResources::getInfo($index, 'id'),
					range(1, ForumResources::size())
				),
				fn (int $forumId) => $this->canViewForum($currentPlayer, $forumId),
			),
		]);
	}

	private function canViewForum(Player $player, int $forumId): bool
	{
		return $forumId < 10
			|| ($forumId >= 10 && $forumId < 20 && $player->isGovernmentMember())
			|| ($forumId >= 20 && $forumId < 30 && $player->isRuler());
	}
}
