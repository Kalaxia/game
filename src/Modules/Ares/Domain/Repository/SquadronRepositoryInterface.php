<?php

namespace App\Modules\Ares\Domain\Repository;

use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<Squadron>
 */
interface SquadronRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return array{
	 *     nbs0: int,
	 *     nbs1: int,
	 *     nbs2: int,
	 *     nbs3: int,
	 *     nbs4: int,
	 *     nbs5: int,
	 *     nbs6: int,
	 *     nbs7: int,
	 *     nbs8: int,
	 *     nbs9: int,
	 *     nbs10: int,
	 *     nbs11: int,
	 * }
	 */
	public function getFactionFleetStats(Color $faction): array;

	public function getCommanderSquadrons(Commander $commander): array;
}
