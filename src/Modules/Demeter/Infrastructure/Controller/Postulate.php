<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicRepositoryInterface;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Postulate extends AbstractController
{
	public function __invoke(
		Request                           $request,
		Player                            $currentPlayer,
		PlayerManager                     $playerManager,
		CandidateRepositoryInterface      $candidateRepository,
		PoliticalEventRepositoryInterface $electionRepository,
		ForumTopicRepositoryInterface     $forumTopicRepository,
		VoteRepositoryInterface           $voteRepository,
		Uuid                              $id,
	): Response {
		$program = $request->request->get('program')
			?? throw new BadRequestHttpException('Missing program');
		//		$chiefChoice = $request->request->get('chiefchoice');
		//		$treasurerChoice = $request->request->get('treasurerchoice');
		//		$warlordChoice = $request->request->get('warlordchoice');
		//		$ministerChoice = $request->request->get('ministerchoice');

		$election = $electionRepository->get($id)
			?? throw $this->createNotFoundException(sprintf('Election %s not found', $id->toBase32()));

		if (!$election->faction->id->equals($currentPlayer->faction->id)) {
			throw $this->createAccessDeniedException('You do not belong to this faction');
		}

		if (!$currentPlayer->isParliamentMember()) {
			throw new ConflictHttpException('Vous ne pouvez pas vous présenter, vous ne faite pas partie de l\'élite.');
		}
		$faction = $currentPlayer->faction;

		if (!$faction->isInCampaign()) {
			throw new ConflictHttpException('Vous ne pouvez présenter ou retirer votre candidature qu\'en période de campagne.');
		}

		if (($candidate = $candidateRepository->getByPoliticalEventAndPlayer($election, $currentPlayer)) !== null) {
			$candidateRepository->remove($candidate);

			$this->addFlash('success', 'Candidature retirée.');

			return $this->redirect($request->headers->get('referer'));
		}
		$candidate = new Candidate(
			id: Uuid::v4(),
            politicalEvent: $election,
			player: $currentPlayer,
			program: $program,
		);

		$candidateRepository->save($candidate);

		$topic = new ForumTopic(
			id: Uuid::v4(),
			title: 'Candidat '.$currentPlayer->name,
			player: $currentPlayer,
			forum: 30,
			faction: $currentPlayer->faction,
		);

		$forumTopicRepository->save($topic);

		if (ColorResource::ADRANITE === $currentPlayer->faction->identifier) {
			$vote = new \App\Modules\Demeter\Model\Election\Vote(
				id: Uuid::v4(),
				player: $currentPlayer,
				candidate: $candidate,
				hasApproved: true,
				votedAt: new \DateTimeImmutable(),
			);

			$voteRepository->save($vote);
		}
		$this->addFlash('success', 'Candidature déposée.');

		return $this->redirectToRoute('view_faction_election', ['candidate' => $candidate->id]);
	}
}
