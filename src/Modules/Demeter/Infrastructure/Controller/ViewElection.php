<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Forum\ForumMessageRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicRepositoryInterface;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class ViewElection extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		PoliticalEventRepositoryInterface $electionRepository,
		CandidateRepositoryInterface $candidateRepository,
		VoteRepositoryInterface $voteRepository,
		PlayerRepositoryInterface $playerRepository,
		ForumTopicRepositoryInterface $forumTopicRepository,
		ForumMessageRepositoryInterface $forumMessageRepository,
		DurationHandler $durationHandler,
		NextElectionDateCalculator $nextElectionDateCalculator,
	): Response {
		$faction = $currentPlayer->faction;

		$election = $electionRepository->getFactionLastPoliticalEvent($faction);

		$data = [
			'faction' => $faction,
			'election' => $election,
		];

		if (null !== $election) {
			$candidates = $candidateRepository->getByPoliticalEvent($election);

			$data['candidates'] = $candidates;
			$data['is_candidate'] = 1 <= count(array_filter(
				$candidates,
				fn (Candidate $candidate) => $candidate->player->id === $currentPlayer->id
			));

			if ($faction->hasOngoingElectionCampaign()) {
				$votes = $voteRepository->getPoliticalEventVotes($election);

				$data['player_vote'] = $voteRepository->getPlayerVote($currentPlayer, $election);
				$data['votes'] = $votes;
				$data['faction_members_count'] = $playerRepository->countByFactionAndStatements($faction, [Player::ACTIVE]);

				$candidate = ($request->query->has('candidate') && ($candidate = $candidateRepository->get(Uuid::fromString($request->query->get('candidate')))) !== null)
					? $candidate
					: ([] !== $candidates ? $candidates[0] : null);

				$data['candidate'] = $candidate;

				if (null !== $candidate) {
					if ($faction->isRoyalistic()) {
						$data['putsch_supporters_count'] = count(array_filter($votes, fn (Vote $vote) => $vote->hasApproved));
						$data['remaining_putsch_time'] = $durationHandler->getRemainingTime(
							$nextElectionDateCalculator->getPutschEndDate($faction)
						);
					}

					$topic = $forumTopicRepository->getByForumAndPlayer(30, $candidate->player);
					// TODO Handle forum topic views
					// $forumTopicManager->updateLastView($topic, $currentPlayer->getId());

					$data['topic'] = $topic;
					$data['topic_messages'] = $forumMessageRepository->getTopicMessages($topic);
				}
			}
		}

		return $this->render('pages/demeter/faction/election.html.twig', $data);
	}
}
