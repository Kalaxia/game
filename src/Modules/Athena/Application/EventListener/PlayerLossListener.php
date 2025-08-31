<?php

namespace App\Modules\Athena\Application\EventListener;

use App\Modules\Gaia\Domain\Event\PlanetOwnerChangeEvent;
use App\Modules\Gaia\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class PlayerLossListener
{
	public function __construct(
		private readonly PlanetRepositoryInterface $planetRepository,
		private readonly PlayerManager             $playerManager,
	) {

	}

	public function __invoke(PlanetOwnerChangeEvent $event): void
	{
		// vérifie si le joueur n'a plus de planète, si c'est le cas, il est mort, on lui redonne une planète
		$previousOwner = $event->getPreviousOwner();
		$planet = $event->getPlanet();

		$oldPlayerPlanets = $this->planetRepository->getPlayerPlanets($previousOwner);
		$oldPlayerPlanetsCount = count($oldPlayerPlanets);
		if (0 === $oldPlayerPlanetsCount || (1 === $oldPlayerPlanetsCount && $oldPlayerPlanets[0]->id->equals($planet->id))) {
			$this->playerManager->reborn($previousOwner);
		}
	}
}
