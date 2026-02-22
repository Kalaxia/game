<?php

namespace App\Modules\Demeter\Domain\Repository\Election;

use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Candidate>
 */
interface CandidateRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?Candidate;

	public function getByElectionAndPlayer(Election $election, Player $player): ?Candidate;

	/**
	 * @return list<Candidate>
	 */
	public function getByElection(Election $election): array;
}
