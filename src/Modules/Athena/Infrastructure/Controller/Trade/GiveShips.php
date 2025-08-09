<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade;

use App\Classes\Library\DateTimeConverter;
use App\Classes\Library\Format;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Domain\Service\CountNeededCommercialShips;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Message\Trade\CommercialShippingMessage;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Athena\Resource\ShipResource;
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
		Request $request,
		GetTravelDuration $getTravelDuration,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		CommercialShippingRepositoryInterface $commercialShippingRepository,
		MessageBusInterface $messageBus,
		OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		OrbitalBaseHelper $orbitalBaseHelper,
		TransactionRepositoryInterface $transactionRepository,
		NotificationRepositoryInterface $notificationRepository,
		CountNeededCommercialShips $countNeededCommercialShips,
		TranslatorInterface $translator,
	): Response {
		$baseId = $request->request->get('baseId') ?? throw new BadRequestHttpException('Missing base id');

		if (!Uuid::isValid($baseId)) {
			throw new BadRequestHttpException('Invalid base id');
		}

		$baseUuid = Uuid::fromString($baseId);
		if ($currentBase->id->equals($baseUuid)) {
			throw new BadRequestHttpException('You cannot send ships to your current base');
		}

		// @TODO fix request format as multiple ships sending isn't possible with this design
		for ($i = 0; $i < ShipResource::SHIP_QUANTITY; ++$i) {
			if ($request->request->has('identifier-'.$i)) {
				$shipType = $i;

				if ($request->request->has('quantity-'.$i)) {
					$ships = $request->request->get('quantity-'.$i) > 0
						? $request->request->get('quantity-'.$i) : 1;
					$ships = intval($ships);
				}

				break;
			}
		}

		if (!isset($shipType) || !isset($ships)) {
			throw new BadRequestHttpException('Missing ship request data');
		}

		if (!ShipResource::isAShipFromDock1($shipType) && !ShipResource::isAShipFromDock2($shipType)) {
			throw new BadRequestHttpException('Invalid ship identifier');
		}

		if ($ships <= 0) {
			throw new BadRequestHttpException('Invalid ship quantity');
		}

		if ($currentBase->getShipStorage()[$shipType] < $ships) {
			throw new ConflictHttpException('You do not have enough ships');
		}

		$commercialShipQuantity = $countNeededCommercialShips(Transaction::TYP_SHIP, $ships, $shipType);
		$totalShips = $orbitalBaseHelper->getBuildingInfo(6, 'level', $currentBase->levelCommercialPlateforme, 'nbCommercialShip');
		$usedShips = 0;

		// TODO make service
		$commercialShippings = $commercialShippingRepository->getByBase($currentBase);
		foreach ($commercialShippings as $commercialShipping) {
			if ($commercialShipping->originBase->id->equals($currentBase->id)) {
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
			base: $currentBase,
			type: Transaction::TYP_SHIP,
			quantity: $ships,
			identifier: $shipType,
			price: 0,
			statement: Transaction::ST_COMPLETED,
			publishedAt: new \DateTimeImmutable(),
			currentRate: $transactionRepository->getExchangeRate(Transaction::TYP_SHIP),
		);
		$transactionRepository->save($tr);

		$departure = new \DateTimeImmutable();
		$otherBase = $orbitalBaseRepository->get($baseUuid)
			?? throw $this->createNotFoundException('Destination base not found');

		$cs = new CommercialShipping(
			id: Uuid::v4(),
			player: $currentPlayer,
			originBase: $currentBase,
			destinationBase: $otherBase,
			transaction: $tr,
			resourceTransported: 0,
			shipQuantity: $commercialShipQuantity,
			departureDate: $departure,
			arrivalDate: $getTravelDuration(
				origin: $currentBase->place,
				destination: $otherBase->place,
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

		$currentBase->removeShips($shipType, $ships);

		$orbitalBaseRepository->save($currentBase);

		if ($currentBase->player->id !== $otherBase->player->id) {

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
						' ' . $translator->trans(sprintf('ship_categories.%s.name', $shipType)) . ' depuis sa base ',
						NotificationBuilder::link(
							$this->generateUrl('map', ['place' => $currentBase->place->id]),
							$currentBase->name,
						),
						'.',
					),
					NotificationBuilder::paragraph(
						'Quand le convoi arrivera, les vaisseaux seront placés dans votre hangar.',
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->generateUrl('switchbase', ['baseId' => $otherBase->id, 'page' => 'market']),
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
