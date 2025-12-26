<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Recycling;

use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Shared\Application\PercentageApplier;

class RecycleResources
{
	public function __invoke(RecyclingMission $recyclingMission, int $extractionPoints, int $balancingPercent): int
	{
		$resourceRecycled = PercentageApplier::toInt($extractionPoints, $recyclingMission->target->resources);
		// Random variation between credit and resources extraction
		$resourceRecycled -= PercentageApplier::toInt($resourceRecycled, $balancingPercent);

		return max($resourceRecycled, 0);
	}
}
