<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\EventListener;

use App\Modules\Galaxy\Domain\Event\PlanetOwnerChangeEvent;
use App\Modules\Galaxy\Manager\SectorManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class SectorListener
{
	public function __construct(
		private SectorManager $sectorManager,
	) {
	}

	#[AsEventListener]
	public function onPlanetOwnerChange(PlanetOwnerChangeEvent $event): void
	{
		$system = $event->planet->system;

		$this->sectorManager->calculateOwnership($system->sector);
	}
}
