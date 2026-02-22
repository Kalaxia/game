<?php

namespace App\Modules\Demeter\Domain\Repository\Election;

use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;

/**
 * @extends EntityRepositoryInterface<Vote>
 */
interface VoteRepositoryInterface extends EntityRepositoryInterface
{
	public function getPlayerVote(Player $player, Election $election): ?Vote;

	/**
	 * @return list<Vote>
	 */
	public function getElectionVotes(Election $election): array;
}
