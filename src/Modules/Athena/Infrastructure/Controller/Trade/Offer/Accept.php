<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Offer;

use App\Classes\Library\DateTimeConverter;
use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Manager\CommercialShippingManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\TransactionManager;
use App\Modules\Athena\Message\Trade\CommercialShippingMessage;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Travel\Domain\Service\GetTravelDuration;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class Accept extends AbstractController
{
	public function __invoke(
		Request                               $request,
		OrbitalBase                           $currentBase,
		Player                                $currentPlayer,
		ColorManager                          $colorManager,
		ColorRepositoryInterface              $colorRepository,
		GetTravelDuration                     $getTravelDuration,
		TransactionManager                    $transactionManager,
		OrbitalBaseManager                    $orbitalBaseManager,
		CommercialShippingManager             $commercialShippingManager,
		CommercialShippingRepositoryInterface $commercialShippingRepository,
		MessageBusInterface                   $messageBus,
		NotificationRepositoryInterface       $notificationRepository,
		PlaceManager                          $placeManager,
		PlayerRepositoryInterface             $playerRepository,
		PlayerManager                         $playerManager,
		TransactionRepositoryInterface        $transactionRepository,
		EntityManagerInterface $entityManager,
		Uuid $id,
	): Response {
		$transaction = $transactionRepository->get($id)
			?? throw $this->createNotFoundException('Transaction not found');
		$commercialShipping = $commercialShippingRepository->getByTransaction($transaction)
			?? throw $this->createNotFoundException('Commercial shipping not found');

		if (!$transaction->isProposed()) {
			throw new ConflictHttpException('Transaction is not proposed');
		}
		$transactionData = $transactionManager->getTransactionData($transaction, $currentBase);

		if (!$currentPlayer->canAfford($transactionData['total_price'])) {
			throw new ConflictHttpException('vous n\'avez pas assez de crédits pour accepter cette proposition');
		}
		// chargement des joueurs
		$buyer = $currentPlayer;
		$seller = $transaction->player;

		// The buyer pays the transaction price + the taxes
		$playerManager->decreaseCredit($buyer, $transactionData['total_price']);
		$playerManager->increaseCredit($seller, $transaction->price);

		// transfert des crédits aux alliances
		if (null !== ($exportFaction = $transaction->base->place->system->sector->faction)) {
			$exportFaction->increaseCredit($transactionData['export_tax']);
		}

		if (null !== ($importFaction = $currentBase->place->system->sector->faction)) {
			$importFaction->increaseCredit($transactionData['import_tax']);
		}

		// gain d'expérience
		$experience = $transaction->getExperienceEarned();
		$playerManager->increaseExperience($seller, $experience);

		// update commercialShipping
		$commercialShipping->destinationBase = $currentBase;
		$commercialShipping->departureDate = new \DateTimeImmutable();
		$commercialShipping->arrivalDate = $getTravelDuration(
			$commercialShipping->originBase->place,
			$currentBase->place,
			$commercialShipping->departureDate,
			TravelType::CommercialShipping,
			$commercialShipping->player,
		);
		$commercialShipping->statement = CommercialShipping::ST_GOING;

		// update transaction statement
		$transaction->statement = Transaction::ST_COMPLETED;
		$transaction->validatedAt = new \DateTimeImmutable();

		// update exchange rate
		$transaction->currentRate = Game::calculateCurrentRate(
			$transactionRepository->getExchangeRate($transaction->type),
			$transaction->type,
			$transaction->quantity,
			$transaction->identifier,
			$transaction->price,
		);

		// notif pour le proposeur
		$n = NotificationBuilder::new()
			->setTitle('Transaction validée')
			->setContent(NotificationBuilder::paragraph(
				NotificationBuilder::link($this->generateUrl('embassy', ['player' => $currentPlayer->id]), $currentPlayer->name),
				' a accepté une de vos propositions dans le marché. Des vaisseaux commerciaux viennent de partir de votre ',
				NotificationBuilder::link($this->generateUrl('map', ['place' => $commercialShipping->originBase->place->id]), 'base'),
				' et se dirigent vers ',
				NotificationBuilder::link($this->generateUrl('map', ['place' => $currentBase->place->id]), $currentBase->name),
				' pour acheminer la marchandise. ',
				NotificationBuilder::divider(),
				sprintf(
					'Vous gagnez %s crédits et %s points d\'expérience.',
					Format::numberFormat($transaction->price),
					Format::numberFormat($experience),
				),
				NotificationBuilder::divider(),
				NotificationBuilder::link(
					$this->generateUrl('switchbase', ['baseId' => $commercialShipping->originBase->id, 'page' => 'sell']),
					'En savoir plus ?',
				),
			))
			->for($transaction->player);
		$notificationRepository->save($n);

		//						if (true === $this->getContainer()->getParameter('data_analysis')) {
		//							$qr = $database->prepare('INSERT INTO
		//						DA_CommercialRelation(`from`, `to`, type, weight, dAction)
		//						VALUES(?, ?, ?, ?, ?)'
		//							);
		//							$qr->execute([$transaction->rPlayer, $session->get('playerId'), $transaction->type, DataAnalysis::creditToStdUnit($transaction->price), Utils::now()]);
		//						}
		$entityManager->flush();

		$messageBus->dispatch(
			new CommercialShippingMessage($commercialShipping->id),
			[DateTimeConverter::to_delay_stamp($commercialShipping->getArrivalDate())],
		);

		$this->addFlash('market_success', 'Proposition acceptée. Les vaisseaux commerciaux sont en route vers votre base orbitale.');

		return $this->redirect($request->headers->get('referer'));
	}
}
