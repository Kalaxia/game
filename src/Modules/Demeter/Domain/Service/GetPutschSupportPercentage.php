<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Service;

use App\Modules\Demeter\Domain\Repository\Election\ElectionRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;

readonly class GetPutschSupportPercentage
{
	public function __construct(
		private ElectionRepositoryInterface $electionRepository,
		private VoteRepositoryInterface $voteRepository,
		private PlayerRepositoryInterface $playerRepository,
	) {
	}

	public function __invoke(Color $faction): float
	{

		$election = $this->electionRepository->getFactionLastElection($faction);
		$votes = $this->voteRepository->getElectionVotes($election);

		$positiveVotesCount = array_reduce(
			$votes,
			static fn(int $carry, Vote $vote) => $carry + ($vote->hasApproved > 0 ? 1 : 0),
			initial: 0,
		);

		// TODO replace by count by IsActiveFactionPlayer specification
		$factionActivePlayersCount = $this->playerRepository->countByFactionAndStatements($faction, [Player::ACTIVE]);

		return PercentageApplier::toFloat($positiveVotesCount, $factionActivePlayersCount + 1);
	}
}
