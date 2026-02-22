<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government\Ruler;

use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class FireMinister extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		EntityManagerInterface $entityManager,
		GetFactionsConfiguration $getFactionsConfiguration,
		PlayerRepositoryInterface $playerRepository,
		NotificationRepositoryInterface $notificationRepository,
		int $id,
	): Response {
		// TODO Replace with voter
		if (!$currentPlayer->isRuler()) {
			throw $this->createAccessDeniedException('Vous n\'êtes pas le chef de votre faction.');
		}

		$minister = $playerRepository->get($id) ?? throw $this->createNotFoundException('Player not found');

		if (!$minister->faction->id->equals($currentPlayer->faction->id)) {
			throw new ConflictHttpException('Vous ne pouvez pas virer un joueur d\'une autre faction.');
		}

		if (!$minister->isGovernmentMember()) {
			throw new ConflictHttpException('Vous ne pouvez choisir qu\'un membre du gouvernement.');
		}

		$statusArray = $getFactionsConfiguration($minister->faction, 'status');

		$notification = NotificationBuilder::new()
			->setTitle('Eviction du gouvernement')
			->setContent(NotificationBuilder::paragraph(
				'Vous avez été renvoyé du poste de ',
				$statusArray[$minister->status - 1],
				' de votre faction.',
			))
			->for($minister);

		$notificationRepository->save($notification);

		$minister->status = Player::PARLIAMENT;

		$entityManager->flush();

		return $this->redirect($request->headers->get('referer'));
	}
}
