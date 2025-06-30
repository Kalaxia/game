<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government\Ruler;

use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ChooseMinister extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		EntityManagerInterface $entityManager,
		GetFactionsConfiguration $getFactionsConfiguration,
		PlayerRepositoryInterface $playerRepository,
		NotificationRepositoryInterface $notificationRepository,
		int $department,
	): Response {
		// TODO Replace with voter
		if (!$currentPlayer->isRuler()) {
			throw $this->createAccessDeniedException('Vous n\'êtes pas le chef de votre faction.');
		}
		$minister = $playerRepository->getGovernmentMember($currentPlayer->faction, $department);

		if (null !== $minister) {
			throw new ConflictHttpException(sprintf('This post is already occupied by %s', $minister->name));
		}

		$rPlayer = $request->request->get('rplayer') ?? throw new BadRequestHttpException('Missing player ID');

		$appointee = $playerRepository->get($rPlayer) ?? throw $this->createNotFoundException('Player not found');
		if (!$appointee->faction->id->equals($currentPlayer->faction->id)) {
			throw $this->createAccessDeniedException('This player is from another faction');
		}
		if (!$appointee->isParliamentMember()) {
			throw new ConflictHttpException('Vous ne pouvez choisir qu\'un membre du sénat.');
		}
		if (!in_array($department, [Player::TREASURER, Player::WARLORD, Player::MINISTER])) {
			throw new ConflictHttpException('Ce département est inconnu.');
		}
		$appointee->status = $department;

		$statusArray = $getFactionsConfiguration($appointee->faction, 'status');

		$notification = NotificationBuilder::new()
			->setTitle('Nomination au gouvernement')
			->setContent(NotificationBuilder::paragraph(
				'Vous avez été choisi pour être le ',
				$statusArray[$department - 1],
				' de votre faction.'
			))
			->for($appointee);
		$notificationRepository->save($notification);

		$entityManager->flush();

		$this->addFlash('success', $appointee->name . ' a rejoint votre gouvernement.');

		return $this->redirect($request->headers->get('referer'));
	}
}
