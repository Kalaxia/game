<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class VoteForCandidate extends AbstractController
{
	public function __construct(
		private readonly CandidateRepositoryInterface $candidateRepository,
	) {
	}

	public function __invoke(
        Request                           $request,
        Player                            $currentPlayer,
        PlayerRepositoryInterface         $playerRepository,
        PoliticalEventRepositoryInterface $electionRepository,
        VoteRepositoryInterface           $voteRepository,
        Uuid                              $electionId,
        Uuid                              $candidateId,
	): Response {
		$election = $electionRepository->get($electionId) ?? throw new NotFoundHttpException('Election not found');
		$candidate = $this->candidateRepository->get($candidateId) ?? throw new NotFoundHttpException('Candidate not found');
		$hasApproved = $request->query->getBoolean('hasApproved');

		if (!$election->faction->id->equals($currentPlayer->faction->id)) {
			throw new ConflictHttpException('Cette election ne se déroule pas dans votre faction.');
		}

		if (null !== $voteRepository->getPlayerVote($currentPlayer, $election)) {
			throw new ConflictHttpException('Vous avez déjà voté.');
		}

		if (MandateState::DemocraticVote !== $election->faction->mandateState) {
			throw new ConflictHttpException('Vous ne pouvez voter pour un candidat qu\'en période d\'élection.');
		}

		$vote = new Vote(
			id: Uuid::v4(),
			player: $currentPlayer,
			candidate: $candidate,
			hasApproved: $hasApproved,
			votedAt: new \DateTimeImmutable(),
		);

		$voteRepository->save($vote);

		$this->addFlash('success', 'Vous avez voté.');

		return $this->redirect($request->headers->get('referer'));
	}
}
