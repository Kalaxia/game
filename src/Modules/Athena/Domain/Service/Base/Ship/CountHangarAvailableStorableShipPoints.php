<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Ship;

use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Galaxy\Domain\Entity\Planet;

class CountHangarAvailableStorableShipPoints
{
	public function __construct(
		private readonly CountQueuedShipPoints      $countQueuedShipPoints,
		private readonly CountStoredShipPoints      $countStoredShipPoints,
		private readonly CountMaxStorableShipPoints $countMaxStorableShipPoints,
	) {
	}

	public function __invoke(Planet $planet, array $shipQueues, DockType $dockType): int
	{
		$maxStorableShipPoints = ($this->countMaxStorableShipPoints)($planet, $dockType);
		$storedShipPoints = ($this->countStoredShipPoints)($planet, $dockType);
		$queuedShipPoints = ($this->countQueuedShipPoints)($shipQueues);

		return $maxStorableShipPoints - $storedShipPoints - $queuedShipPoints;
	}
}
