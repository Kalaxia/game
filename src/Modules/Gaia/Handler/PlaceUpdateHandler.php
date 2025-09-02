<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Handler;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Message\PlanetUpdateMessage;
use App\Modules\Shared\Application\Service\CountMissingSystemUpdates;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class PlaceUpdateHandler
{
	public function __construct(
		private ClockInterface $clock,
		private PlaceRepositoryInterface $placeRepository,
		private CountMissingSystemUpdates $countMissingSystemUpdates,
	) {
	}

	public function __invoke(PlanetUpdateMessage $message): void
	{
		$place = $this->placeRepository->get($message->planetId)
			?? throw new \RuntimeException(sprintf('Place %s not found', $message->planetId));

		$missingUpdatesCount = ($this->countMissingSystemUpdates)($place);
		if (0 === $missingUpdatesCount) {
			return;
		}
		// update time
		$place->updatedAt = $this->clock->now();
		$place->resources = min(
			$place->resources + $place->getProducedResources() * $missingUpdatesCount,
			$place->getMaxResources(),
		);
		$place->danger = min(
			$place->danger + Planet::REPOPDANGER * $missingUpdatesCount,
			$place->maxDanger,
		);

		$this->placeRepository->save($place);
	}
}
