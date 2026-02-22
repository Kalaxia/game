<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Offer;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\Trade\GetBaseCommercialShippingData;
use App\Modules\Athena\Domain\Service\CountAvailableCommercialShips;
use App\Modules\Athena\Domain\Service\CountNeededCommercialShips;
use App\Modules\Athena\Domain\Service\Transaction\GetMaxPriceRelativeToRate;
use App\Modules\Athena\Domain\Service\Transaction\GetMinPriceRelativeToRate;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Galaxy\Manager\PlanetManager;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\Turbo\TurboBundle;

class Propose extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		Planet $currentPlanet,
		PlanetManager $planetManager,
		PlanetHelper $planetHelper,
		GetMinPriceRelativeToRate $getMinPriceRelativeToRate,
		GetMaxPriceRelativeToRate $getMaxPriceRelativeToRate,
		GetBaseCommercialShippingData $getBaseCommercialShippingData,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		TransactionRepositoryInterface $transactionRepository,
		CountNeededCommercialShips $countNeededCommercialShips,
		CountAvailableCommercialShips $countAvailableCommercialShips,
		CommercialShippingRepositoryInterface $commercialShippingRepository,
		HubInterface $mercureHub,
		SerializerInterface $serializer,
		ValidatorInterface $validator,
	): Response {
		$type = $request->query->get('type') ?? throw new BadRequestHttpException('Missing type');
		$quantity = $request->request->getInt('quantity');
		// TODO ATM the identifier contains ship number or commander UUID. Modify the form to keep only ship number
		$identifier = $request->query->get('identifier');
		$price = $request->request->get('price') ?? throw new BadRequestHttpException('Missing price');

		// TODO Move to validator component constraint
		switch ($type) {
			case Transaction::TYP_RESOURCE:
				if (0 === $quantity) {
					throw new BadRequestHttpException('Invalid quantity');
				}
				if ($currentPlanet->resourcesStorage < $quantity) {
					throw new ConflictHttpException('The current base has not enough resources to make that sale');
				}
				$identifier = 0;
				break;
			case Transaction::TYP_SHIP:
				if (null === ($shipCategory = ShipCategory::tryFrom($identifier))) {
					throw new BadRequestHttpException('Invalid ship identifier');
				}

				if (0 === $quantity) {
					throw new BadRequestHttpException('Invalid quantity');
				}

				if ($currentPlanet->getShipStorage()[$shipCategory->value] < $quantity) {
					throw new ConflictHttpException('The current base has not enough ships to make that sale');
				}

				break;
			case Transaction::TYP_COMMANDER:
				if (null === $identifier || $identifier < 1) {
					throw new BadRequestHttpException('Invalid commander ID');
				}
				break;
			default:
				throw new \LogicException('Invalid transaction type');
		}
		// TODO transform into service
		$minPrice = $getMinPriceRelativeToRate($type, $quantity, $identifier);
		$maxPrice = $getMaxPriceRelativeToRate($type, $quantity, $identifier);

		// TODO Move to a validator constraint (same as above ?)
		if ($price < $minPrice) {
			throw new BadRequestHttpException('Le prix que vous avez fixé est trop bas. Une limite inférieure est fixée selon la catégorie de la vente.');
		} elseif ($price > $maxPrice) {
			throw new BadRequestHttpException('Le prix que vous avez fixé est trop haut. Une limite supérieure est fixée selon la catégorie de la vente.');
		}

		$remainingShips = $countAvailableCommercialShips($currentPlanet);
		// determine commercialShipQuantity needed
		$commercialShipQuantity = $countNeededCommercialShips($type, $quantity, $identifier);

		if ($remainingShips < $commercialShipQuantity) {
			throw new ConflictHttpException('Vous n\'avez pas assez de vaisseaux de transport disponibles.');
		}
		switch ($type) {
			case Transaction::TYP_RESOURCE:
				$planetManager->decreaseResources($currentPlanet, $quantity);
				break;
			case Transaction::TYP_SHIP:
				$currentPlanet->removeShips($identifier, $quantity);
				break;
			case Transaction::TYP_COMMANDER:
				$commander = $commanderRepository->get(Uuid::fromString($identifier))
					?? throw $this->createNotFoundException('Commander not found');

				// TODO replace with Voter
				if ($commander->player->id !== $currentPlayer->id) {
					throw $this->createAccessDeniedException('This commander does not belong to you');
				}

				if ($commander->isOnSale()) {
					throw new ConflictHttpException('This commander is already on sale');
				}
				$identifier = 0;
				$commander->statement = Commander::ONSALE;
				$commanderManager->emptySquadrons($commander);
				break;
		}
		// création de la transaction
		$tr = new Transaction(
			id: Uuid::v4(),
			player: $currentPlayer,
			base: $currentPlanet,
			type: $type,
			quantity: $quantity,
			identifier: $identifier,
			publishedAt: new \DateTimeImmutable(),
			currentRate: $transactionRepository->getLastCompletedTransaction($type)->currentRate,
			commander: $commander ?? null,
			price: $price,
			commercialShipQuantity: $commercialShipQuantity,
			statement: Transaction::ST_PROPOSED,
		);

		$validator->validate($tr);

		$transactionRepository->save($tr);

		// création du convoi
		$cs = new CommercialShipping(
			id: Uuid::v4(),
			player: $currentPlayer,
			originBase: $currentPlanet,
			transaction: $tr,
			shipQuantity: $commercialShipQuantity,
			statement: CommercialShipping::ST_WAITING,
		);
		$commercialShippingRepository->save($cs);

		$transactionRepository->save($tr);

		$mercureHub->publish(new Update(
			'/trade-offers',
			$this->renderView('components/planet/trade/turbo/broadcast/new_transaction.stream.html.twig', [
				'transaction' => $tr,
			]),
		));

		$this->addFlash('market_success', 'Votre proposition a été envoyée sur le marché.');

		if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
			$request->setRequestFormat(TurboBundle::STREAM_FORMAT);

			return $this->render('components/planet/trade/turbo/new_transaction.stream.html.twig', [
				'commercial_shipping' => $cs,
				'used_ships' => $getBaseCommercialShippingData($currentPlanet)['used_ships'],
				'max_ships' => $planetHelper->getInfo(
					PlanetResource::COMMERCIAL_PLATEFORME,
					'level',
					$currentPlanet->levelCommercialPlateforme,
					'nbCommercialShip',
				),
			]);
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
