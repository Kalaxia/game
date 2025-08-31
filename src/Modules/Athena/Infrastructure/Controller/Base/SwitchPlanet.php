<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base;

use App\Modules\Gaia\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class SwitchPlanet extends AbstractController
{
	public function __invoke(
		Request                   $request,
		Player                    $currentPlayer,
		PlanetRepositoryInterface $planetRepository,
		Uuid                      $planetId,
		string                    $page
	): Response {
		if (null === ($planet = $planetRepository->get($planetId))) {
			throw new NotFoundHttpException('Base not found');
		}

		if ($planet->player->id !== $currentPlayer->id) {
			throw new AccessDeniedHttpException('This planet does not belong to you');
		}

		$request->getSession()->get('playerParams')->add('planet', $planet->id);

		return $this->redirectToRoute(match ($page) {
			'generator' => 'generator',
			'refinery' => 'refinery',
			'docks' => 'docks',
			'technosphere' => 'technosphere',
			'commercialroute', 'sell', 'market' => 'trade_market',
			'school' => 'school',
			'spatioport' => 'spatioport',
			default => 'base_overview',
		}, $request->query->all());
	}
}
