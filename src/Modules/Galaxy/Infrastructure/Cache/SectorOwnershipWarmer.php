<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\Cache;

use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Galaxy\Manager\SectorOwnershipCalculator;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

readonly class SectorOwnershipWarmer implements CacheWarmerInterface
{
	public function __construct(
		private SectorRepositoryInterface $sectorRepository,
		private SectorOwnershipCalculator $sectorOwnershipCalculator,
	) {
	}

	public function warmUp(string $cacheDir, ?string $buildDir = null): array
	{
		foreach ($this->sectorRepository->findAll() as $sector) {
			$this->sectorOwnershipCalculator->refreshSectorOwnership($sector);
		}

		return [];
	}

	public function isOptional(): bool
	{
		return true;
	}
}
