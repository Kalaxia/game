<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Classes\Library\Parser;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Application\Persister\NotificationPersister;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\CreditTransactionRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class SendCredits extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		Parser $parser,
		PlayerManager $playerManager,
		PlayerRepositoryInterface $playerRepository,
		CreditTransactionRepositoryInterface $creditTransactionRepository,
		NotificationPersister $notificationPersister,
	): Response {
		$name = $request->request->get('name') ?? throw new BadRequestHttpException('Missing receiver name');
		$credit = $request->request->getInt('quantity');
		$text = $request->request->getAlnum('text');

		$name = $parser->protect($name);
		$text = $parser->parse($text);

		if (!$currentPlayer->isTreasurer()) {
			throw $this->createAccessDeniedException('Seul le responsable financier de votre faction peut faire cette action.');
		}
		if (strlen($text) > 500) {
			throw new BadRequestHttpException('le texte ne doit pas dépasser les 500 caractères');
		}

		if ($credit <= 0) {
			throw new BadRequestHttpException('envoi de crédits impossible - il faut envoyer un nombre entier positif');
		}
		$faction = $currentPlayer->faction;
		$receiver = $playerRepository->getByName($name) ?? throw $this->createNotFoundException('Receiver not found');

		if ($faction->credits < $credit) {
			throw new BadRequestHttpException('Vous ne pouvez pas envoyer plus que ce que vous possédez');
		}
		$faction->decreaseCredit($credit);
		$playerManager->increaseCredit($receiver, $credit);

		// create the transaction
		$ct = new CreditTransaction(
			id: Uuid::v4(),
			playerSender: null,
			playerReceiver: $receiver,
			factionSender: $faction,
			factionReceiver: null,
			amount: $credit,
			createdAt: new \DateTimeImmutable(),
			comment: $text,
		);
		$creditTransactionRepository->save($ct);

		$notification = NotificationBuilder::new()
			->setTitle('Réception de crédits')
			->setContent(NotificationBuilder::paragraph(
				'Votre faction vous a envoyé des crédits',
				('' !== $text)
					? sprintf(' avec le message suivant : %s%s', NotificationBuilder::divider(), $text)
					: '.',
				NotificationBuilder::resourceBox(
					NotificationBuilder::RESOURCE_TYPE_CREDIT,
					$credit,
					1 == $credit ? 'crédit reçu' : 'crédits reçus',
				),
			))
			->forPlayer($receiver);
		$notificationPersister->saveFromBuilder($notification);

		$this->addFlash('success', 'Crédits envoyés');

		return $this->redirect($request->headers->get('referer'));
	}
}
