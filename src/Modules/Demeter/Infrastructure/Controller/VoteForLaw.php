<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\VoteLawRepositoryInterface;
use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Model\Law\VoteLaw;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class VoteForLaw extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		LawManager $lawManager,
		LawRepositoryInterface $lawRepository,
		VoteLawRepositoryInterface $voteLawRepository,
		Uuid $id,
	): Response {
		$choice = $request->query->get('choice')
			?? throw new BadRequestHttpException('Informations manquantes.');

		if (!$currentPlayer->isSenator()) {
			throw $this->createAccessDeniedException('Vous n\'avez pas le droit de voter.');
		}

		$law = $lawRepository->get($id)
			?? throw $this->createNotFoundException('Cette loi n\'existe pas.');

		if (Law::VOTATION !== $law->isBeingVoted()) {
			throw new ConflictHttpException('Cette loi est déjà votée.');
		}
		if ($voteLawRepository->hasVoted($currentPlayer, $law)) {
			throw new ConflictHttpException('Vous avez déjà voté.');
		}
		$vote = new VoteLaw(
			id: Uuid::v4(),
			player: $currentPlayer,
			law: $law,
			vote: $choice,
			votedAt: new \DateTimeImmutable(),
		);

		$voteLawRepository->save($vote);

		return $this->redirect($request->headers->get('referer'));
	}
}
