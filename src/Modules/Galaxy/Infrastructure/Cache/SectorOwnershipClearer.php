<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Infrastructure\Cache;

use App\Modules\Galaxy\Manager\SectorOwnershipCalculator;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

readonly class SectorOwnershipClearer implements CacheClearerInterface
{
	public function __construct(
		private SectorOwnershipCalculator $sectorOwnershipCalculator,
	) {
	}

	public function clear(string $cacheDir): void
	{
		$this->sectorOwnershipCalculator->clearSectorOwnership();
	}
}
