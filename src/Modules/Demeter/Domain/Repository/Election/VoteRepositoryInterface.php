<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Repository\Election;

use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;

/**
 * @extends EntityRepositoryInterface<Vote>
 */
interface VoteRepositoryInterface extends EntityRepositoryInterface
{
	public function getPlayerVote(Player $player, PoliticalEvent $politicalEvent): ?Vote;

	/**
	 * @return list<Vote>
	 */
	public function getPoliticalEventVotes(PoliticalEvent $politicalEvent): array;
}
