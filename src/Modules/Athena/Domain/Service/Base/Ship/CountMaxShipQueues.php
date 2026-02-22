<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Ship;

use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Helper\PlanetHelper;

readonly class CountMaxShipQueues
{
	public function __construct(private PlanetHelper $planetHelper)
	{
	}

	public function __invoke(Planet $planet, DockType $dockType): int
	{
		return $this->planetHelper->getBuildingInfo(
			$dockType->getBuildingNumber(),
			'level',
			$dockType->getLevel($planet),
			'nbQueues',
		);
	}
}
