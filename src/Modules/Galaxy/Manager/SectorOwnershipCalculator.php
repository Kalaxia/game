<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Manager;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Galaxy\Domain\Event\SectorOwnerChangeEvent;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\SystemRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class SectorOwnershipCalculator
{
	private const int CONTROLLED_SYSTEM_POINTS = 2;

	public function __construct(
		private EventDispatcherInterface $eventDispatcher,
		private ColorRepositoryInterface $colorRepository,
		private TagAwareCacheInterface $cache,
		private SystemRepositoryInterface $systemRepository,
		private SectorRepositoryInterface $sectorRepository,
		private PlanetRepositoryInterface $planetRepository,
		private LoggerInterface $logger,
		#[Autowire('%galaxy.sector_minimal_score%')]
		private int $sectorMinimalScore,
		#[Autowire('%galaxy.scores%')]
		private array $scores = [],
	) {
	}

	public function getSectorOwnership(Sector $sector): array
	{
		return $this->cache->get(
			sprintf('sector-%d-ownership', $sector->identifier),
			function (ItemInterface $item) use ($sector) {
				$item->tag('sectors_ownership');

				return $this->calculateOwnership($sector);
			},
		);
	}

	public function clearSectorOwnership(): void
	{
		$this->cache->invalidateTags(['sectors_ownership']);

		$this->logger->info('Sectors ownership cache cleared');
	}

	/**
	 * @return array<int, int>
	 */
	public function refreshSectorOwnership(Sector $sector): array
	{
		$this->cache->delete(sprintf('sector-%d-ownership', $sector->identifier));

		return $this->getSectorOwnership($sector);
	}

	/**
	 * Returns a sorted array with faction identifiers as keys and their ownership score as values
	 * The highest score is first.
	 *
	 * @return array<int, int>
	 */
	public function calculateOwnership(Sector $sector): array
	{
		$systems = $this->systemRepository->getSectorSystems($sector);
		$planets = $this->planetRepository->getSectorPlanets($sector);
		$scores = [];

		foreach ($planets as $planet) {
			if (null === ($player = $planet->player)) {
				continue;
			}

			$scores[$player->faction->identifier] =
				(!empty($scores[$player->faction->identifier]))
				? $scores[$player->faction->identifier] + $this->scores[$planet->typeOfBase]
				: $this->scores[$planet->typeOfBase]
			;
		}
		// For each system, the owning faction gains two points
		foreach ($systems as $system) {
			if (null === $system->faction) {
				continue;
			}
			$scores[$system->faction->identifier] = (!empty($scores[$system->faction->identifier]))
				? $scores[$system->faction->identifier] + self::CONTROLLED_SYSTEM_POINTS
				: self::CONTROLLED_SYSTEM_POINTS;
		}
		$scores[0] = 0;
		arsort($scores);

		$newColor = key($scores);
		$score = $scores[$newColor];
		$hasEnoughPoints = $score >= $this->sectorMinimalScore;

		$currentFactionIdentifier = $sector->faction?->identifier ?? 0;

		if (!$hasEnoughPoints && null !== $sector->faction) {
			if (!$sector->prime) {
				$this->eventDispatcher->dispatch(new SectorOwnerChangeEvent(
					sector: $sector,
					winner: null,
					loser: $sector->faction,
				));

				$sector->faction = null;
			}
		} elseif ($currentFactionIdentifier !== $newColor && $score > $scores[$currentFactionIdentifier]) {
			$newOwner = $this->colorRepository->getOneByIdentifier($newColor)
				?? throw new \LogicException(sprintf('Faction %d not found', $newColor));

			$this->eventDispatcher->dispatch(new SectorOwnerChangeEvent(
				sector: $sector,
				winner: $newOwner,
				loser: $sector->faction,
			));

			$sector->faction = $newOwner;
			// Once a sector is conquered, it is not considered as a spawn sector anymore
			$sector->prime = false;
		}

		$this->sectorRepository->save($sector);

		return $scores;
	}
}
