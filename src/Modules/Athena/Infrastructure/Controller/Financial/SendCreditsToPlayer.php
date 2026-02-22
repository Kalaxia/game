<?php

namespace App\Modules\Athena\Infrastructure\Controller\Financial;

use App\Classes\Library\Format;
use App\Classes\Library\Parser;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
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

class SendCreditsToPlayer extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CreditTransactionRepositoryInterface $creditTransactionRepository,
		PlayerRepositoryInterface $playerRepository,
		NotificationRepositoryInterface $notificationRepository,
		Parser $parser,
		PlayerManager $playerManager,
	): Response {
		$name = $request->request->get('name');
		$credit = $request->request->getInt('quantity');
		$text = $request->request->get('text');

		if (null === $name || 0 === $credit) {
			throw new BadRequestHttpException('Le nom ou le montant est invalide');
		}

		if (500 < strlen($text)) {
			throw new BadRequestHttpException('Le message ne doit pas dépasser 500 caractères');
		}

		if (null === ($receiver = $playerRepository->getByName($name))) {
			throw $this->createNotFoundException('Le bénéficiaire renseigné n\'existe pas');
		}

		if ($receiver->id === $currentPlayer->id) {
			return $this->redirectToRoute('financial_transfers');
		}

		if ($credit > $currentPlayer->getCredits()) {
			throw new BadRequestHttpException('Vous ne disposez pas du montant nécessaire');
		}

		// input protection
		$name = $parser->protect($name);
		$text = $parser->parse($text);

		$playerManager->decreaseCredit($currentPlayer, $credit);
		$playerManager->increaseCredit($receiver, $credit);

		// create the transaction
		$ct = new CreditTransaction(
			id: Uuid::v4(),
			factionReceiver: null,
			factionSender: null,
			playerSender: $currentPlayer,
			playerReceiver: $receiver,
			amount: $credit,
			createdAt: new \DateTimeImmutable(),
			comment: $text,
		);

		$creditTransactionRepository->save($ct);

		$n = NotificationBuilder::new()
			->setTitle('Réception de crédits')
			->setContent(
				NotificationBuilder::paragraph(
					NotificationBuilder::link(
						$this->generateUrl('embassy', ['player' => $currentPlayer->id]),
						$currentPlayer->name,
					),
					' vous a envoyé des crédits',
					!empty($text)
						? sprintf(' avec le message suivant :%s"%s"', NotificationBuilder::divider(), $text)
						: '.',
					NotificationBuilder::resourceBox('credit', Format::numberFormat($credit), 1 == $credit ? 'crédit reçu' : 'crédits reçus')
				)
			)
			->for($receiver);

		$notificationRepository->save($n);

		$this->addFlash('success', 'Crédits envoyés');

		return $this->redirectToRoute('financial_transfers');
	}
}
