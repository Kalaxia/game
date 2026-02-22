<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Galaxy\Application\Registry\CurrentPlayerPlanetsRegistry;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewOverview extends AbstractController
{
	public function __construct(
		private readonly CurrentPlayerPlanetsRegistry $currentPlayerPlanetsRegistry,
		private readonly CommanderRepositoryInterface $commanderRepository,
		private readonly PlanetRepositoryInterface $planetRepository,
	) {
	}

	public function __invoke(Player $currentPlayer): Response
	{
		return $this->render('pages/ares/fleet/overview.html.twig', [
			'obsets' => $this->getObsets($currentPlayer),
		]);
	}

	private function getObsets(Player $currentPlayer): array
	{
		// set d'orbitale base
		$obsets = [];
		foreach ($this->currentPlayerPlanetsRegistry->all() as $planet) {
			$obsets[] = [
				'info' => [
					'id' => $planet->id->toRfc4122(),
					'name' => $planet->name,
					'type' => $planet->typeOfBase,
				],
			];
		}

		// commander manager : yours
		$commanders = $this->commanderRepository->getPlayerCommanders(
			$currentPlayer,
			[Commander::AFFECTED, Commander::MOVING],
			['c.base' => 'DESC'],
		);

		for ($i = 0; $i < count($obsets); ++$i) {
			foreach ($commanders as $commander) {
				if ($commander->base->id->toRfc4122() === $obsets[$i]['info']['id']) {
					$obsets[$i]['fleets'][] = $commander;
				}
			}
		}
		// ship in dock
		$playerPlanets = $this->planetRepository->getPlayerPlanets($currentPlayer);

		for ($i = 0; $i < count($obsets); ++$i) {
			foreach ($playerPlanets as $planet) {
				if ($planet->id->toRfc4122() == $obsets[$i]['info']['id']) {
					$obsets[$i]['dock'] = $planet->getShipStorage();
				}
			}
		}

		return $obsets;
	}
}
