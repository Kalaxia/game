<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Planet\Building;

use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\Trade\GetBaseCommercialShippingData;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Galaxy\Resource\PlanetResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TradeMarketController extends AbstractController
{
	#[Route(
		path: '/trade-market/{mode<market|sell|resource>}',
		name: 'trade_market',
		defaults: ['mode' => 'market'],
		methods: [Request::METHOD_GET],
	)]
	public function __invoke(
		GetBaseCommercialShippingData $getBaseCommercialShippingsData,
		Planet $currentBase,
		PlanetHelper $planetHelper,
		TransactionRepositoryInterface $transactionRepository,
		string $mode,
	): Response {
		if (0 === $currentBase->levelCommercialPlateforme) {
			return $this->redirectToRoute('base_overview');
		}

		return $this->render('pages/athena/trade_market.html.twig', [
			'mode' => $mode,
			'max_ships' => $planetHelper->getInfo(
				PlanetResource::COMMERCIAL_PLATEFORME,
				'level',
				$currentBase->levelCommercialPlateforme,
				'nbCommercialShip',
			),
			'resources_current_rate' => $transactionRepository->getLastCompletedTransaction(Transaction::TYP_RESOURCE)->currentRate,
			'resource_transactions' => $transactionRepository->getProposedTransactions(Transaction::TYP_RESOURCE),
			'commander_current_rate' => $transactionRepository->getLastCompletedTransaction(Transaction::TYP_COMMANDER)->currentRate,
			'commander_transactions' => $transactionRepository->getProposedTransactions(Transaction::TYP_COMMANDER),
			'ship_current_rate' => $transactionRepository->getLastCompletedTransaction(Transaction::TYP_SHIP)->currentRate,
			'ship_transactions' => $transactionRepository->getProposedTransactions(Transaction::TYP_SHIP),
			'commercial_shippings' => $getBaseCommercialShippingsData($currentBase),
		]);
	}
}
