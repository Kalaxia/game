<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\EventListener;

use App\Modules\Galaxy\Event\PlaceOwnerChangeEvent;
use App\Modules\Galaxy\Manager\SectorManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PlaceOwnerChangeEvent::class, method: 'onPlaceOwnerChange')]
readonly class SectorListener
{
	public function __construct(
		private SectorManager $sectorManager,
	) {
	}

	public function onPlaceOwnerChange(PlaceOwnerChangeEvent $event): void
	{
		$system = $event->planet->system;

		$this->sectorManager->calculateOwnership($system->sector);
	}
}
