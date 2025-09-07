<?php

namespace App\Modules\Athena\Application\EventListener;

use App\Modules\Galaxy\Domain\Event\PlanetOwnerChangeEvent;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * vérifie si le joueur n'a plus de planète, si c'est le cas, il est mort, on lui redonne une planète.
 */
#[AsEventListener]
readonly class PlayerLossListener
{
	public function __construct(
		private PlanetRepositoryInterface $planetRepository,
		private PlayerManager $playerManager,
	) {
	}

	public function __invoke(PlanetOwnerChangeEvent $event): void
	{
		$previousOwner = $event->previousOwner;
		$planet = $event->planet;

		if (null === $previousOwner) {
			return;
		}

		$oldPlayerPlanets = $this->planetRepository->getPlayerPlanets($previousOwner);
		$oldPlayerPlanetsCount = count($oldPlayerPlanets);
		if (0 === $oldPlayerPlanetsCount || (1 === $oldPlayerPlanetsCount && $oldPlayerPlanets[0]->id->equals($planet->id))) {
			$this->playerManager->reborn($previousOwner);
		}
	}
}
