<?php

namespace App\Shared\Infrastructure\Twig;

use App\Shared\Application\Handler\DurationHandler;
use App\Shared\Domain\Model\TravellerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TravellerExtension extends AbstractExtension
{
	public function __construct(
		private readonly DurationHandler $durationHandler,
	) {
	}

	#[\Override]
	public function getFilters(): array
	{
		return [
			new TwigFilter(
				'travel_spent_time',
				fn (TravellerInterface $traveller, bool $reversed = false) => $this->durationHandler->getDiff(
					$reversed ? $traveller->getArrivalDate() : $traveller->getDepartureDate(),
					new \DateTimeImmutable(),
				),
			),
			new TwigFilter('travel_remaining_time', fn (TravellerInterface $traveller) => $this->durationHandler->getDiff(
				new \DateTimeImmutable(),
				$traveller->getArrivalDate(),
			)),
			new TwigFilter('travel_total_time', fn (TravellerInterface $traveller) => $this->durationHandler->getDiff(
				$traveller->getDepartureDate(),
				$traveller->getArrivalDate(),
			)),
		];
	}
}
