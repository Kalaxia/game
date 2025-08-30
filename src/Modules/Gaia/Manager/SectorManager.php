<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Manager;

use App\Classes\Redis\RedisManager;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SystemRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class SectorManager
{
	private const int CONTROLLED_SYSTEM_POINTS = 2;

	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private RedisManager                   $redisManager,
		private SystemRepositoryInterface      $systemRepository,
		private SectorRepositoryInterface $sectorRepository,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		#[Autowire('%gaia.sector_minimal_score%')]
		private int $sectorMinimalScore,
		#[Autowire('%gaia.scores%')]
		private array $scores = [],
	) {
	}

	/**
	 * Returns a sorted array with faction identifiers as keys and their ownership score as values
	 * The highest score is first
	 *
	 * @return array<int, int>
	 */
	public function calculateOwnership(Sector $sector): array
	{
		$systems = $this->systemRepository->getSectorSystems($sector);
		$bases = $this->orbitalBaseRepository->getSectorBases($sector);
		$scores = [];

		foreach ($bases as $base) {
			$player = $base->player;

			$scores[$player->faction->identifier] =
				(!empty($scores[$player->faction->identifier]))
				? $scores[$player->faction->identifier] + $this->scores[$base->typeOfBase]
				: $this->scores[$base->typeOfBase]
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

		if (!$hasEnoughPoints) {
			// If this is a prime sector, we do not pull back the color from the sector
			// TODO check behavior if another faction has taken the prime sector before
			if (!$sector->prime) {
				$sector->faction = null;
			}
		} elseif ($currentFactionIdentifier !== $newColor && $score > $scores[$currentFactionIdentifier]) {
			$sector->faction = $this->colorRepository->getOneByIdentifier($newColor);
		}

		$this->sectorRepository->save($sector);

		$this->redisManager->getConnection()->set('sector:' . $sector->id, serialize($scores));

		return $scores;
	}
}
