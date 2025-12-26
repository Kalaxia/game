<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Recycling;

use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Shared\Application\PercentageApplier;

class RecycleCredits
{
	public function __invoke(RecyclingMission $recyclingMission, int $extractionPoints, int $percent): int
	{
		$creditRecycled = PercentageApplier::toInt($extractionPoints, $recyclingMission->target->credits) * 10;
		// Random variation between credit and resources extraction
		$creditRecycled += PercentageApplier::toInt($creditRecycled, $percent);

		return max($creditRecycled, 0);
	}
}
