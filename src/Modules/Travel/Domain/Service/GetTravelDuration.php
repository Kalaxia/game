<?php

declare(strict_types=1);

namespace App\Modules\Travel\Domain\Service;

use App\Classes\Library\Game;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;

readonly class GetTravelDuration
{
	public function __construct(
		private CalculateTravelTime $calculateTravelTime,
		private DurationHandler $durationHandler,
	) {
	}

	public function __invoke(
        Planet             $origin,
        Planet             $destination,
        \DateTimeImmutable $departureDate,
        TravelType         $travelType = TravelType::Fleet,
        Player|null        $player = null,
	): \DateTimeImmutable {
		$time = ($this->calculateTravelTime)($origin, $destination, $travelType, $player);

		if ($travelType === TravelType::CommercialShipping) {
			$time = intval(round($time * Game::COMMERCIAL_TIME_TRAVEL));
		}

		return $this->durationHandler->getDurationEnd($departureDate, $time);
	}
}
