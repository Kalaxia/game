<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Repository\Election;

use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Candidate>
 */
interface CandidateRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?Candidate;

	public function getByPoliticalEventAndPlayer(PoliticalEvent $politicalEvent, Player $player): ?Candidate;

	/**
	 * @return list<Candidate>
	 */
	public function getByPoliticalEvent(PoliticalEvent $politicalEvent): array;
}
