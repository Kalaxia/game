<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Service\Election;

use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\PoliticalEvent;

readonly class SortCandidatesByVotes
{
	public function __construct(
		private CandidateRepositoryInterface $candidateRepository,
		private VoteRepositoryInterface $voteRepository,
	) {
	}

	/**
	 * @return array<string, array{
	 *     candidate: Candidate,
	 *     votes_count: int,
	 * }>
	 */
	public function __invoke(PoliticalEvent $election): array
	{
		$votes = $this->voteRepository->getPoliticalEventVotes($election);
		$ballot = $this->initializeBallot($election);

		foreach ($votes as $vote) {
			$candidateId = $vote->candidate->id->toBase32();
			if (!array_key_exists($candidateId, $ballot)) {
				throw new \RuntimeException(sprintf(
					'Vote for candidate with id %s that is not part of the election %s',
					$candidateId,
					$election->id->toBase32(),
				));
			}
			++$ballot[$candidateId]['votes_count'];
		}

		uasort($ballot, fn ($a, $b) => $b['votes_count'] <=> $a['votes_count']);

		arsort($ballot);

		return $ballot;
	}

	/**
	 * @return array<string, array{
	 *     candidate: Candidate,
	 *     votes_count: int,
	 * }>
	 */
	private function initializeBallot(PoliticalEvent $election): array
	{
		return array_reduce(
			$this->candidateRepository->getByPoliticalEvent($election),
			function (array $carry, Candidate $candidate) {
				$carry[$candidate->id->toBase32()] = [
					'candidate' => $candidate,
					'votes_count' => 0,
				];

				return $carry;
			},
			[],
		);
	}
}
