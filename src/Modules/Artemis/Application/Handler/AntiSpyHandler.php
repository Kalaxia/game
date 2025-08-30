<?php

declare(strict_types=1);

namespace App\Modules\Artemis\Application\Handler;

use App\Classes\Library\Game;
use App\Modules\Ares\Model\Commander;
use App\Modules\Gaia\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Travel\Domain\Service\GetTravelDuration;
use App\Shared\Application\Handler\DurationHandler;

readonly class AntiSpyHandler
{
	public function __construct(
		private DurationHandler $durationHandler,
		private GetTravelDuration $getTravelDuration,
		private GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
	) {

	}

	// TODO Maybe transform it in Twig extension method ?
	public function getAntiSpyRadius(int $investment, int $mode = Game::ANTISPY_DISPLAY_MODE): float
	{
		return Game::ANTISPY_DISPLAY_MODE === $mode
			// en pixels : sert à l'affichage
			? sqrt($investment / 3.14) * 20
			// en position du jeu (250x250)
			: sqrt($investment / 3.14);
	}

	/**
	 * TODO Figure out which mean the true values
	 *
	 * @return list<\DateTimeImmutable|true>
	 */
	public function getAntiSpyEntryTime(Place $startPlace, Place $destinationPlace, Commander $commander): array
	{
		$arrivalDate = $commander->getArrivalDate();
		// dans le même système
		if ($startPlace->system->id->equals($destinationPlace->system->id)) {
			return [true, true, true];
		}
		$departureDate = new \DateTimeImmutable();
		$arrivalDate = ($this->getTravelDuration)(
			origin: $startPlace,
			destination: $destinationPlace,
			departureDate: $departureDate,
			player: $commander->player
		);
		$duration = $this->durationHandler->getDiff($departureDate, $arrivalDate);

		$secRemaining = $arrivalDate->getTimestamp() - time();
		$ratioRemaining = $secRemaining / $duration;

		$distance = ($this->getDistanceBetweenPlaces)($startPlace, $destinationPlace);
		$distanceRemaining = $this->getRemainingSeconds($distance, $ratioRemaining);

		$antiSpyRadius = $this->getAntiSpyRadius($destinationPlace->base->iAntiSpy, 1);

		if ($distanceRemaining < $antiSpyRadius / 3) {
			return [true, true, true];
		}

		if ($distanceRemaining < $antiSpyRadius / 3 * 2) {
			$ratio = ($antiSpyRadius / 3) / $distanceRemaining;
			$sec = $this->getRemainingSeconds($ratio, $secRemaining);
			$newDate = $this->durationHandler->getDurationEnd($arrivalDate, -$sec);

			return [true, true, $newDate];
		}

		if ($distanceRemaining < $antiSpyRadius) {
			$ratio = ($antiSpyRadius / 3 * 2) / $distanceRemaining;
			$sec = $this->getRemainingSeconds($ratio, $secRemaining);
			$newDate1 = $this->durationHandler->getDurationEnd($arrivalDate, -$sec);

			$ratio = ($antiSpyRadius / 3) / $distanceRemaining;
			$sec = $this->getRemainingSeconds($ratio, $secRemaining);
			$newDate2 = $this->durationHandler->getDurationEnd($arrivalDate, -$sec);

			return [true, $newDate1, $newDate2];
		}

		$ratio = $antiSpyRadius / $distanceRemaining;
		$sec = $this->getRemainingSeconds($ratio, $secRemaining);
		$newDate1 = $this->durationHandler->getDurationEnd($arrivalDate, -$sec);

		$ratio = ($antiSpyRadius / 3 * 2) / $distanceRemaining;
		$sec = $this->getRemainingSeconds($ratio, $secRemaining);
		$newDate2 = $this->durationHandler->getDurationEnd($arrivalDate, -$sec);

		$ratio = ($antiSpyRadius / 3) / $distanceRemaining;
		$sec = $this->getRemainingSeconds($ratio, $secRemaining);
		$newDate3 = $this->durationHandler->getDurationEnd($arrivalDate, -$sec);

		return [$newDate1, $newDate2, $newDate3];
	}

	/**
	 * TODO Sanitize the parameters and include ratio calculation
	 */
	public function getRemainingSeconds(float|int $ratio, float|int $secRemaining): int
	{
		return intval(floor($ratio * $secRemaining));
	}
}
