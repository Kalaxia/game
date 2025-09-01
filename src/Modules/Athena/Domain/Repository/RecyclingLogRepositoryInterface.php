<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\RecyclingLog;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;

interface RecyclingLogRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return list<RecyclingLog>
	 */
	public function getBaseActiveMissionsLogs(Planet $base): array;

	public function getPlayerRecycledCreditsSince(Player $player, \DateTimeImmutable $since): int;
}
