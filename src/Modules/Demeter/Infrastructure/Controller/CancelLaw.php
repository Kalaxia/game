<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class CancelLaw extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		LawManager $lawManager,
		LawRepositoryInterface $lawRepository,
		Uuid $id,
	): Response {
		if (($law = $lawRepository->get($id)) === null) {
			throw $this->createNotFoundException('Cette loi n\'existe pas.');
		}
		// TODO replace with voter
		if ($currentPlayer->status !== LawResources::getInfo($law->type, 'department')) {
			throw $this->createAccessDeniedException('Vous n\'avez pas le droit d\'annuler cette loi.');
		}

		// @TODO implement law cancellation
		return $this->redirect($request->headers->get('referer'));
	}
}
