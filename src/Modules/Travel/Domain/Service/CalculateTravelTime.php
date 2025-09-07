<?php

declare(strict_types=1);

namespace App\Modules\Travel\Domain\Service;

use App\Modules\Ares\Application\Handler\GetFleetSpeed;
use App\Modules\Ares\Model\Commander;
use App\Modules\Galaxy\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class CalculateTravelTime
{
	public function __construct(
		private GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
		private GetFleetSpeed $getFleetSpeed,
		private PlayerBonusManager $playerBonusManager,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function __invoke(Place $from, Place $to, TravelType $travelType, Player|null $player = null): int
	{
		return match ($this->timeMode) {
			TimeMode::Fast => match ($travelType) {
				TravelType::Fleet => 300,
				TravelType::CommercialShipping => 120,
				TravelType::RecyclingShips => 600,
			},
			TimeMode::Standard => $from->system->id->equals($to->system->id)
				? $this->getTimeTravelInSystem($from, $to)
				: $this->getTimeTravelOutOfSystem($from, $to, $player),
		};
	}

	private function getTimeTravelInSystem(Place $from, Place $to): int
	{
		$distance = abs($from->position - $to->position);

		return intval(round((Commander::COEFFMOVEINSYSTEM * $distance) * ((40 - $distance) / 50) + 180));
	}

	private function getTimeTravelOutOfSystem(Place $from, Place $to, Player|null $player): int
	{
		$playerBonus = null !== $player ? $this->playerBonusManager->getBonusByPlayer($player) : null;
		$distance = ($this->getDistanceBetweenPlaces)($from, $to);

		$time = Commander::COEFFMOVEOUTOFSYSTEM;
		$time += round((Commander::COEFFMOVEINTERSYSTEM * $distance) / ($this->getFleetSpeed)($playerBonus));

		return intval($time);
	}
}
