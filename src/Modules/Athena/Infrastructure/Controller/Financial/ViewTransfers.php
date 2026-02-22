<?php

namespace App\Modules\Athena\Infrastructure\Controller\Financial;

use App\Modules\Zeus\Domain\Repository\CreditTransactionRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewTransfers extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		CreditTransactionRepositoryInterface $creditTransactionRepository,
	): Response {
		return $this->render('pages/athena/financial/transfers.html.twig', [
			'sendings' => $creditTransactionRepository->getAllBySender($currentPlayer),
			'receivings' => $creditTransactionRepository->getAllByPlayerReceiver($currentPlayer),
		]);
	}
}
