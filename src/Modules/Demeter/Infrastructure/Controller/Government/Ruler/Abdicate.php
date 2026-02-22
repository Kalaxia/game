<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government\Ruler;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
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

class Abdicate extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		GetFactionsConfiguration $getFactionsConfiguration,
		PlayerRepositoryInterface $playerRepository,
		NotificationRepositoryInterface $notificationRepository,
		EntityManagerInterface $entityManager,
		NextElectionDateCalculator $nextElectionDateCalculator,
	): Response {
		$faction = $currentPlayer->faction;

		if (!$currentPlayer->isRuler()) {
			throw $this->createAccessDeniedException('Vous n\'êtes pas le chef de votre faction.');
		}

		$mandateDuration = $nextElectionDateCalculator->getMandateDuration($faction);

		if ($faction->isDemocratic()) {
			if (!$faction->isInMandate()) {
				throw new ConflictHttpException('Des élections sont déjà en cours.');
			}
			$faction->lastElectionHeldAt = new \DateTimeImmutable(sprintf('-%d seconds', $mandateDuration));
			$this->addFlash('success', 'Des élections anticipées vont être lancées.');

			return $this->redirect($request->headers->get('referer'));
		}
		$playerId = $request->request->get('rplayer') ?? throw new BadRequestHttpException('Missing player ID');

		$heir = $playerRepository->get($playerId) ?? throw $this->createNotFoundException('Player not found');

		// TODO Replace with a voter
		if (!$heir->faction->id->equals($faction->id)) {
			throw new BadRequestHttpException('Selected player is from another faction');
		}
		if (!$heir->isParliamentMember()) {
			throw new ConflictHttpException('Vous ne pouvez choisir qu\'un membre du sénat ou du gouvernement.');
		}
		if (!$faction->isInMandate()) {
			throw new ConflictHttpException('vous ne pouvez pas abdiquer pendant un putsch.');
		}
		$heir->status = Player::CHIEF;
		// The player is now a member of Parliament
		$currentPlayer->status = Player::PARLIAMENT;
		$request->getSession()->get('playerInfo')->add('status', Player::PARLIAMENT);

		$entityManager->flush();

		$statusArray = $getFactionsConfiguration($heir->faction, 'status');

		$notification = NotificationBuilder::new()
			->setTitle('Héritier du Trône.')
			->setContent(NotificationBuilder::paragraph(
				'Vous avez été choisi par le ',
				$statusArray[5],
				' de votre faction pour être son successeur, vous prenez la tête du gouvernement immédiatement.',
			))
			->for($heir);
		$notificationRepository->save($notification);

		$this->addFlash('success', $heir->name.' est désigné comme votre successeur.');

		return $this->redirect($request->headers->get('referer'));
	}
}
