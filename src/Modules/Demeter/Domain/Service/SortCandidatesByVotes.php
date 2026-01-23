<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Service;

use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\Election;

readonly class SortCandidatesByVotes
{
	public function __construct(
		private VoteRepositoryInterface $voteRepository,
	) {
	}

	public function __invoke(Election $election): array
	{
		$votes = $this->voteRepository->getElectionVotes($election);
		/** @var array<string, array{candidate: Candidate, votes_count: int}> $ballot */
		$ballot = [];

		foreach ($votes as $vote) {
			$candidateId = $vote->candidate->id->toBase32();
			if (!array_key_exists($candidateId, $ballot)) {
				$ballot[$candidateId] = [
					'candidate' => $vote->candidate,
					'votes_count' => 0,
				];
			}
			++$ballot[$candidateId]['votes_count'];
		}

		uasort($ballot, fn($a, $b) => $b['votes_count'] <=> $a['votes_count']);

		arsort($ballot);

		return $ballot;
	}
}
