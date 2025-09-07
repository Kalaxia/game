<?php

namespace App\Modules\Zeus\Infrastructure\EventListener;

use App\Modules\Galaxy\Application\Registry\CurrentPlayerPlanetsRegistry;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener]
readonly class CurrentPlayerListener
{
	public function __construct(
		private PlayerRepositoryInterface    $playerRepository,
		private PlayerBonusManager           $playerBonusManager,
		private PlanetRepositoryInterface    $planetRepository,
		private CurrentPlayerRegistry        $currentPlayerRegistry,
		private CurrentPlayerPlanetsRegistry $currentPlayerPlanetsRegistry,
		private CurrentPlayerBonusRegistry   $currentPlayerBonusRegistry,
	) {
	}

	public function __invoke(RequestEvent $event): void
	{
		$request = $event->getRequest();

		if (!$request->hasPreviousSession() || null === ($playerId = $request->getSession()->get('playerId'))) {
			return;
		}

		$player = $this->playerRepository->get($playerId);
		$this->currentPlayerRegistry->set($player);
		$this->currentPlayerPlanetsRegistry->setPlanets($this->planetRepository->getPlayerPlanets($player));
		$this->currentPlayerPlanetsRegistry->setCurrentPlanet($request->getSession()->get('playerParams')->get('planet'));

		$bonus = $this->playerBonusManager->getBonusByPlayer($player);
		$this->currentPlayerBonusRegistry->setPlayerBonus($bonus);
	}
}
