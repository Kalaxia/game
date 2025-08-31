<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Offer;

use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\GetMaxResourceStorage;
use App\Modules\Athena\Domain\Service\Base\Trade\GetBaseCommercialShippingData;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Gaia\Helper\PlanetHelper;
use App\Modules\Gaia\Manager\PlanetManager;
use App\Modules\Gaia\Resource\PlanetResource;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\Turbo\TurboBundle;

class Cancel extends AbstractController
{
	public function __invoke(
        Request                               $request,
        Player                                $currentPlayer,
        GetMaxResourceStorage                 $getMaxStorage,
        GetBaseCommercialShippingData         $getBaseCommercialShippingData,
        PlanetHelper                          $planetHelper,
        PlanetManager                         $planetManager,
        PlayerManager                         $playerManager,
        HubInterface                          $mercure,
        CommercialShippingRepositoryInterface $commercialShippingRepository,
        TransactionRepositoryInterface        $transactionRepository,
        Uuid                                  $id,
	): Response {

		$transaction = $transactionRepository->get($id)
			?? throw $this->createNotFoundException('Transaction not found');

		$commercialShipping = $commercialShippingRepository->getByTransaction($transaction)
			?? throw $this->createNotFoundException('Commercial shipping not found');

		if (!$transaction->isProposed()) {
			throw new ConflictHttpException('This transaction is not currently proposed on the market');
		}

		if ($transaction->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('This transaction does not belong to you');
		}

		$planet = $transaction->base;

		if (!$currentPlayer->canAfford($transaction->getPriceToCancelOffer())) {
			throw new ConflictHttpException('You cannot afford the cancellation fee');
		}

		switch ($transaction->type) {
			case Transaction::TYP_RESOURCE:
				$storageSpace = $getMaxStorage($planet) - $planet->resourcesStorage;

				if ($storageSpace < $transaction->quantity) {
					throw new ConflictHttpException('Vous n\'avez pas assez de place dans votre Stockage pour stocker les ressources. Videz un peu le hangar et revenez plus tard pour annuler cette offre.');
				}
				$planetManager->increaseResources($planet, $transaction->quantity, true);
				break;
			case Transaction::TYP_SHIP:
				$planet->addShips($transaction->identifier, $transaction->quantity);
				break;
			case Transaction::TYP_COMMANDER:
				$transaction->commander->statement = Commander::RESERVE;
				break;
			default:
				throw new \LogicException('Invalid transaction type');
		}
		// débit des crédits au joueur
		$playerManager->decreaseCredit($currentPlayer, $transaction->getPriceToCancelOffer());

		// annulation de l'envoi commercial (libération des vaisseaux de commerce)
		$commercialShippingRepository->remove($commercialShipping);

		// update transaction statement
		$transaction->statement = Transaction::ST_CANCELED;
		$transaction->validatedAt = new \DateTimeImmutable();

		$this->addFlash('market_success', match ($transaction->type) {
			Transaction::TYP_RESOURCE => 'Annulation de la proposition commerciale. Les vaisseaux commerciaux sont à nouveau disponibles et vous récupérez vos ressources.',
			Transaction::TYP_SHIP => 'Annulation de la proposition commerciale. Les vaisseaux commerciaux sont à nouveau disponibles et vous récupérez vos vaisseaux de combat.',
			Transaction::TYP_COMMANDER => 'Annulation de la proposition commerciale. Les vaisseaux commerciaux sont à nouveau disponibles et votre commandant est placé à l\'école de commandement.',
		});

		$transactionRepository->save($transaction);

		$mercure->publish(new Update(
			'/trade-offers',
			$this->renderView('components/planet/trade/turbo/broadcast/remove_transaction.stream.html.twig', [
				'transaction' => $transaction,
			]),
		));

		if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
			$request->setRequestFormat(TurboBundle::STREAM_FORMAT);

			return $this->render('components/planet/trade/turbo/remove_transaction.stream.html.twig', [
				'commercial_shipping' => $commercialShipping,
				'used_ships' => $getBaseCommercialShippingData($planet)['used_ships'],
				'max_ships' => $planetHelper->getInfo(
					PlanetResource::COMMERCIAL_PLATEFORME,
					'level',
					$planet->levelCommercialPlateforme,
					'nbCommercialShip',
				),
			]);
		}

		return $this->redirectToRoute('trade_market');
	}
}
