<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Service\Election;

use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Zeus\Model\Player;

/**
 * This service selects the faction new leader from a pre-sorted ballot
 * If some candidates are ex aequo, then the leader is chosen randomly between them
 */
class SelectNewLeaderFromBallot
{
	/**
	 * @param array<string, array{
	 *     candidate: Candidate,
	 *     votes_count: int,
	 * }> $ballot Array of already sorted candidates */
	public function __invoke(array $ballot): Player
	{
		if (empty($ballot)) {
			throw new \RuntimeException('Ballot cannot be empty');
		}

		$maxVoteCandidates = [array_shift($ballot)];
		$votesCount = current($maxVoteCandidates)['votes_count'];

		foreach ($ballot as $ballotItem) {
			if ($ballotItem['votes_count'] === $votesCount) {
				$maxVoteCandidates[] = $ballotItem;
			}
		}

		return $this->selectRandomCandidate(array_map(
			fn (array $ballotItem) => $ballotItem['candidate'],
			$maxVoteCandidates,
		));
	}

	/**
	 * @param list<Candidate> $candidates
	 */
	private function selectRandomCandidate(array $candidates): Player
	{
		return $candidates[array_rand($candidates)]->player;
	}
}
