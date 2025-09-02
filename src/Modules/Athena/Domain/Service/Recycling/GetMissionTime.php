<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Recycling;

use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Travel\Domain\Service\CalculateTravelTime;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class GetMissionTime
{
	public function __construct(
		private CalculateTravelTime $calculateTravelTime,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function __invoke(Planet $startPlace, Planet $destinationPlace, Player $player)
	{
		$travelTime = ($this->calculateTravelTime)($startPlace, $destinationPlace, TravelType::RecyclingShips, $player);

		return (2 * $travelTime) + match ($this->timeMode) {
			TimeMode::Fast => 3600,
			TimeMode::Standard => RecyclingMission::RECYCLING_TIME,
		};
	}
}
