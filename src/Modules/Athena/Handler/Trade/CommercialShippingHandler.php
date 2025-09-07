<?php

namespace App\Modules\Athena\Handler\Trade;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Manager\CommercialShippingManager;
use App\Modules\Athena\Message\Trade\CommercialShippingMessage;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
readonly class CommercialShippingHandler
{
	public function __construct(
		private DurationHandler $durationHandler,
		private CommercialShippingManager $commercialShippingManager,
		private CommercialShippingRepositoryInterface $commercialShippingRepository,
		private MessageBusInterface $messageBus,
		private NotificationRepositoryInterface $notificationRepository,
		private UrlGeneratorInterface $urlGenerator,
	) {
	}

	public function __invoke(CommercialShippingMessage $message): void
	{
		$cs = $this->commercialShippingRepository->get($message->getCommercialShippingId())
			?? throw new \RuntimeException(sprintf('Commercial shipping %s not found', $message->getCommercialShippingId()));
		$transaction = $cs->transaction;
		$planet = $cs->originBase;
		$destOB = $cs->destinationBase;
		$commander = (null !== $transaction && Transaction::TYP_COMMANDER === $transaction->type)
				? $transaction->commander
				: null
		;

		switch ($cs->statement) {
			case CommercialShipping::ST_GOING:
				// shipping arrived, delivery of items to rBaseDestination
				$this->commercialShippingManager->deliver($cs, $transaction, $destOB, $commander);
				// prepare commercialShipping for moving back
				$cs->statement = CommercialShipping::ST_MOVING_BACK;
				$timeToTravel = $this->durationHandler->getDiff($cs->getDepartureDate(), $cs->getArrivalDate());
				$cs->departureDate = $cs->getArrivalDate();
				$cs->arrivalDate = $this->durationHandler->getDurationEnd($cs->getArrivalDate(), $timeToTravel);

				$this->commercialShippingRepository->save($cs);

				$this->messageBus->dispatch(
					new CommercialShippingMessage($cs->id),
					[DateTimeConverter::to_delay_stamp($cs->getArrivalDate())],
				);
				break;
			case CommercialShipping::ST_MOVING_BACK:
				// shipping arrived, release of the commercial ships
				// send notification
				$notification = NotificationBuilder::new()
					->setTitle('Retour de livraison')
					->setContent(NotificationBuilder::paragraph(
						(1 === $cs->shipQuantity)
							? 'Votre vaisseau commercial est de retour sur votre '
							: 'Vos vaisseaux commerciaux sont de retour sur votre ',
						NotificationBuilder::link($this->urlGenerator->generate('map', ['place' => $planet->id]), 'base orbitale'),
						' après avoir livré du matériel sur une autre ',
						NotificationBuilder::link($this->urlGenerator->generate('map', ['place' => $destOB->id]), 'base'),
						NotificationBuilder::divider(),
						(1 === $cs->shipQuantity)
							? 'Votre vaisseau de commerce est à nouveau disponible pour faire d\'autres transactions ou routes commerciales.'
							: 'Vos '.$cs->shipQuantity.' vaisseaux de commerce sont à nouveau disponibles pour faire d\'autres transactions ou routes commerciales.',
					))
					->for($cs->player);

				$this->notificationRepository->save($notification);
				// delete commercialShipping
				$this->commercialShippingRepository->remove($cs);
				break;
			default:
				break;
		}
	}
}
