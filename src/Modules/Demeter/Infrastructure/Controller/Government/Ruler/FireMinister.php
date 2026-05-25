<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Controller\Government\Ruler;

use App\Modules\Demeter\Domain\Event\Government\FiredMinisterEvent;
use App\Modules\Zeus\Domain\Enum\PlayerStatus;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class FireMinister extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		EventDispatcherInterface $eventDispatcher,
		EntityManagerInterface $entityManager,
		PlayerRepositoryInterface $playerRepository,
		TranslatorInterface $translator,
		int $id,
	): Response {
		// TODO Replace with voter
		if (!$currentPlayer->isRuler()) {
			throw $this->createAccessDeniedException('Vous n\'êtes pas le chef de votre faction.');
		}

		$minister = $playerRepository->get($id) ?? throw $this->createNotFoundException('Player not found');
		$status = $minister->status;

		if (!$minister->faction->id->equals($currentPlayer->faction->id)) {
			throw new ConflictHttpException('Vous ne pouvez pas virer un joueur d\'une autre faction.');
		}

		if (!$minister->isGovernmentMember()) {
			throw new ConflictHttpException('Vous ne pouvez choisir qu\'un membre du gouvernement.');
		}

		$minister->status = PlayerStatus::Parliament;

		$entityManager->flush();

		$eventDispatcher->dispatch(new FiredMinisterEvent($currentPlayer, $minister, $status));

		return $this->redirect($request->headers->get('referer'));
	}
}
