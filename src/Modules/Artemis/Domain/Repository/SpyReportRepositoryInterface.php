<?php

namespace App\Modules\Artemis\Domain\Repository;

use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<SpyReport>
 */
interface SpyReportRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?SpyReport;

	/**
	 * @param Uuid[] $places
	 *
	 * @return SpyReport[]
	 */
	public function getSystemReports(Player $player, array $places): array;

	/**
	 * @return SpyReport[]
	 */
	public function getPlayerReports(Player $player): array;

	public function deletePlayerReports(Player $player): int;
}
