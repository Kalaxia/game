<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Ship;

use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Model\OrbitalBase;

readonly class CountStoredShipPoints
{
	public function __construct(
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	public function __invoke(OrbitalBase $base, DockType $dockType): int
	{
		$storage = $base->getShipStorage();
		$inStorage = 0;

		foreach ($dockType->getShipRange() as $m) {
			$inStorage += ($this->getShipCategoriesConfiguration)($m, 'pev') * ($storage[$m] ?? 0);
		}

		return $inStorage;
	}
}
