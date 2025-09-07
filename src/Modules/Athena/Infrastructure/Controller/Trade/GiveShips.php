<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade;

use App\Classes\Library\DateTimeConverter;
use App\Classes\Library\Format;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Domain\Service\CountNeededCommercialShips;
use App\Modules\Athena\Message\Trade\CommercialShippingMessage;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Travel\Domain\Service\GetTravelDuration;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

class GiveShips extends AbstractController
{
	public function __invoke(
        Request                               $request,
        GetTravelDuration                     $getTravelDuration,
        Player                                $currentPlayer,
        Planet                                $currentPlanet,
        CommercialShippingRepositoryInterface $commercialShippingRepository,
        MessageBusInterface                   $messageBus,
        PlanetRepositoryInterface             $planetRepository,
        PlanetHelper                          $planetHelper,
        TransactionRepositoryInterface        $transactionRepository,
        NotificationRepositoryInterface       $notificationRepository,
        CountNeededCommercialShips            $countNeededCommercialShips,
        TranslatorInterface                   $translator,
	): Response {
		$planetId = $request->request->get('planetId') ?? throw new BadRequestHttpException('Missing base id');

		if (!Uuid::isValid($planetId)) {
			throw new BadRequestHttpException('Invalid base id');
		}

		$baseUuid = Uuid::fromString($planetId);
		if ($currentPlanet->id->equals($baseUuid)) {
			throw new BadRequestHttpException('You cannot send ships to your current base');
		}

		// @TODO fix request format as multiple ships sending isn't possible with this design
		foreach (ShipCategory::cases() as $shipCategory) {
			$shipIdentifier = $shipCategory->value;
			if (!$request->request->has('identifier-'.$shipIdentifier)) {
				continue;
			}

			if ($request->request->has('quantity-'.$shipIdentifier)) {
				$ships = $request->request->get('quantity-'.$shipIdentifier) > 0
					? $request->request->get('quantity-'.$shipIdentifier) : 1;
				$ships = intval($ships);
			}

			break;
		}

		if (!isset($shipIdentifier) || !isset($ships)) {
			throw new BadRequestHttpException('Missing ship request data or invalid ship identifier');
		}

		if ($ships <= 0) {
			throw new BadRequestHttpException('Invalid ship quantity');
		}

		if ($currentPlanet->getShipStorage()[$shipIdentifier] < $ships) {
			throw new ConflictHttpException('You do not have enough ships');
		}

		$commercialShipQuantity = $countNeededCommercialShips(Transaction::TYP_SHIP, $ships, $shipIdentifier);
		$totalShips = $planetHelper->getBuildingInfo(6, 'level', $currentPlanet->levelCommercialPlateforme, 'nbCommercialShip');
		$usedShips = 0;

		// TODO make service
		$commercialShippings = $commercialShippingRepository->getByPlanet($currentPlanet);
		foreach ($commercialShippings as $commercialShipping) {
			if ($commercialShipping->originBase->id->equals($currentPlanet->id)) {
				$usedShips += $commercialShipping->shipQuantity;
			}
		}

		$remainingShips = $totalShips - $usedShips;

		if ($remainingShips < $commercialShipQuantity) {
			throw new ConflictHttpException('Missing transport ships to perform this operation');
		}

		// création de la transaction
		// TODO why a transaction ? Must destroy the price rates
		// To handle the ships quantity.
		// TODO Refactor ships quantity and move this field to commercial shipping entity
		$tr = new Transaction(
			id: Uuid::v4(),
			player: $currentPlayer,
			base: $currentPlanet,
			type: Transaction::TYP_SHIP,
			quantity: $ships,
			identifier: $shipIdentifier,
			price: 0,
			statement: Transaction::ST_COMPLETED,
			publishedAt: new \DateTimeImmutable(),
			currentRate: $transactionRepository->getExchangeRate(Transaction::TYP_SHIP),
		);
		$transactionRepository->save($tr);

		$departure = new \DateTimeImmutable();
		$otherBase = $planetRepository->get($baseUuid)
			?? throw $this->createNotFoundException('Destination base not found');

		$cs = new CommercialShipping(
			id: Uuid::v4(),
			player: $currentPlayer,
			originBase: $currentPlanet,
			destinationBase: $otherBase,
			transaction: $tr,
			resourceTransported: 0,
			shipQuantity: $commercialShipQuantity,
			departureDate: $departure,
			arrivalDate: $getTravelDuration(
				origin: $currentPlanet,
				destination: $otherBase,
				player: $currentPlayer,
				departureDate: $departure,
				travelType: TravelType::CommercialShipping,
			),
			statement: CommercialShipping::ST_GOING,
		);

		$commercialShippingRepository->save($cs);

		$messageBus->dispatch(
			new CommercialShippingMessage($cs->id),
			[DateTimeConverter::to_delay_stamp($cs->getArrivalDate())],
		);

		$currentPlanet->removeShips($shipIdentifier, $ships);

		$planetRepository->save($currentPlanet);

		if ($currentPlanet->player->id !== $otherBase->player->id) {

			$notification = NotificationBuilder::new()
				->setTitle('Envoi de vaisseaux')
				->setContent(
					NotificationBuilder::paragraph(
						$otherBase->name,
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->generateUrl('embassy', ['player' => $currentPlayer->id]),
							$currentPlayer->name,
						),
						' a lancé un convoi de ',
						NotificationBuilder::bold(Format::numberFormat($ships)),
						' ' . $translator->trans(sprintf('ship_categories.%s.name', $shipIdentifier)) . ' depuis sa base ',
						NotificationBuilder::link(
							$this->generateUrl('map', ['place' => $currentPlanet->id]),
							$currentPlanet->name,
						),
						'.',
					),
					NotificationBuilder::paragraph(
						'Quand le convoi arrivera, les vaisseaux seront placés dans votre hangar.',
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->generateUrl('switchplanet', ['planetId' => $otherBase->id, 'page' => 'market']),
							'vers la place du commerce →',
						),
					)
				)
				->for($otherBase->player);
			$notificationRepository->save($notification);
		}

		$this->addFlash('success', 'Vaisseaux envoyés');

		return $this->redirect($request->headers->get('referer'));
	}
}
