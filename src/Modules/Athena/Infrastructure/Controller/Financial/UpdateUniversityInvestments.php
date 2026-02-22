<?php

namespace App\Modules\Athena\Infrastructure\Controller\Financial;

use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateUniversityInvestments extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		PlayerManager $playerManager,
	): Response {
		$investment = abs($request->request->getInt('credit'));

		if (500000 < $investment) {
			throw new BadRequestHttpException('La limite maximale d\'investissement dans l\'Université est de 500\'000 crédits.');
		}

		$playerManager->updateUniversityInvestment($currentPlayer, $investment);

		$this->addFlash('success', 'L\'investissement dans l\'université a été modifié');

		return $this->redirectToRoute('financial_investments');
	}
}
