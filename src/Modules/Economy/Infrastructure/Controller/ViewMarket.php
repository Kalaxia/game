<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Controller;

use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Economy\Domain\Service\SearchMarketOffers;
use App\Modules\Galaxy\Domain\Entity\Planet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
	path: '/economy/market',
	name: 'view_market',
	methods: [Request::METHOD_GET],
)]
class ViewMarket extends AbstractController
{
	public function __invoke(
		Planet             $currentPlanet,
		SearchMarketOffers $searchMarketProducts,
	): Response {
		$offers = $searchMarketProducts(Activity::Shipyard, $currentPlanet->system);

		return $this->render('pages/economy/market.html.twig', [
			'offers' => $offers,
		]);
	}
}
