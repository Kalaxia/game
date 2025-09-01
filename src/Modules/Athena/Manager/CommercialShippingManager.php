<?php

namespace App\Modules\Athena\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Classes\Library\Format;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Message\Trade\CommercialShippingMessage;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Manager\PlanetManager;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Application\SchedulerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class CommercialShippingManager implements SchedulerInterface
{
	public function __construct(
		private PlanetManager                         $planetManager,
		private NotificationRepositoryInterface       $notificationRepository,
		private MessageBusInterface                   $messageBus,
		private CommercialShippingRepositoryInterface $commercialShippingRepository,
		private TranslatorInterface                   $translator,
	) {
	}

	public function schedule(): void
	{
		$shippings = $this->commercialShippingRepository->getMoving();

		foreach ($shippings as $commercialShipping) {
			$this->messageBus->dispatch(
				new CommercialShippingMessage($commercialShipping->id),
				[DateTimeConverter::to_delay_stamp($commercialShipping->arrivalDate)],
			);
		}
	}

	/**
	 * TODO: add more data and links in notifications.
	 */
	public function deliver(
        CommercialShipping $commercialShipping,
        Transaction|null   $transaction,
        Planet             $destOB,
        Commander|null     $commander
	): void {
		if (null !== $transaction && $transaction->isCompleted()) {
			switch ($transaction->type) {
				case Transaction::TYP_RESOURCE:
					$this->planetManager->increaseResources($destOB, $transaction->quantity, true);

					// notif pour l'acheteur
					$notification = NotificationBuilder::new()
						->setTitle('Ressources reçues')
						->setContent(NotificationBuilder::paragraph(
							'Vous avez reçu les ',
							$transaction->quantity,
							' ressources que vous avez achetées au marché.',
						))
						->for($destOB->player);
					$this->notificationRepository->save($notification);

					break;
				case Transaction::TYP_SHIP:
					$destOB->addShips($transaction->identifier, $transaction->quantity);

					$pluralS = Format::plural($transaction->quantity);
					$pluralX = Format::plural($transaction->quantity, 'x');
					// notif pour l'acheteur
					$notification = NotificationBuilder::new()
						->setTitle('Vaisseau'.$pluralX.' reçu'.$pluralS)
						->setContent(NotificationBuilder::paragraph(
							'Vous avez reçu le',
							$pluralS,
							' vaisseau',
							$pluralX,
							' de type ',
							$this->translator->trans(sprintf('ship_categories.%d.name', $transaction->identifier)),
							null === $commercialShipping->resourceTransported
								? ' que vous avez acheté au marché.'
								: ' envoyé'.$pluralS.' par un marchand galactique.',
							NotificationBuilder::divider(),
							1 === $transaction->quantity
								? 'Il a été ajouté à votre hangar.'
								: 'Ils ont été ajoutés à votre hangar.',
						))
						->for($destOB->player);
					$this->notificationRepository->save($notification);
					break;
				case Transaction::TYP_COMMANDER:
					$commander->statement = Commander::RESERVE;
					$commander->player = $destOB->player;
					$commander->base = $destOB;

					// notif pour l'acheteur
					$notification = NotificationBuilder::new()
						->setTitle('Commandant reçu')
						->setContent(NotificationBuilder::paragraph(
							'Le commandant ',
							$commander->name,
							' que vous avez acheté au marché est bien arrivé.',
							NotificationBuilder::divider(),
							'Il se trouve pour le moment dans votre école de commandement',
						))
						->for($destOB->player);
					$this->notificationRepository->save($notification);
					break;
				default:
					throw new \LogicException('type de transaction inconnue dans deliver()');
			}

			$commercialShipping->statement = CommercialShipping::ST_MOVING_BACK;
		} elseif (null === $transaction && null === $commercialShipping->transaction && null !== $commercialShipping->resourceTransported) {
			// resource sending

			$this->planetManager->increaseResources($destOB, $commercialShipping->resourceTransported, true);

			// notif for the player who receive the resources
			$notification = NotificationBuilder::new()
				->setTitle('Ressources reçues')
				->setContent(NotificationBuilder::paragraph(
					'Vous avez bien reçu les ',
					$commercialShipping->resourceTransported,
					' ressources sur votre base orbitale ',
					$destOB->name,
					'.',
				))
				->for($destOB->player);
			$this->notificationRepository->save($notification);

			$commercialShipping->statement = CommercialShipping::ST_MOVING_BACK;
		} else {
			throw new \RuntimeException('impossible de délivrer ce chargement');
		}
	}
}
