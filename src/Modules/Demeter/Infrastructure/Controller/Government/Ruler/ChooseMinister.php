<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Controller\Government\Ruler;

use App\Modules\Demeter\Domain\Event\Government\NewMinisterEvent;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Hermes\Application\Persister\NotificationPersister;
use App\Modules\Zeus\Domain\Enum\PlayerStatus;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
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
		EventDispatcherInterface $eventDispatcher,
		EntityManagerInterface $entityManager,
		GetFactionsConfiguration $getFactionsConfiguration,
		PlayerRepositoryInterface $playerRepository,
		NotificationPersister $notificationPersister,
		PlayerStatus $department,
	): Response {
		// TODO Replace with voter
		if (!$currentPlayer->isRuler()) {
			throw $this->createAccessDeniedException('Vous n\'êtes pas le chef de votre faction.');
		}
		$minister = $playerRepository->getGovernmentMember($currentPlayer->faction, $department);

		$rPlayer = $request->request->getInt('rplayer') ?? throw new BadRequestHttpException('Missing player ID');

		$appointee = $playerRepository->get($rPlayer) ?? throw $this->createNotFoundException('Player not found');
		if (!$appointee->faction->id->equals($currentPlayer->faction->id)) {
			throw $this->createAccessDeniedException('This player is from another faction');
		}
		if (!$appointee->isParliamentMember()) {
			throw new ConflictHttpException('Vous ne pouvez choisir qu\'un membre du sénat.');
		}
		if (!in_array($department, [PlayerStatus::Treasurer, PlayerStatus::Warlord, PlayerStatus::Minister])) {
			throw new ConflictHttpException('Ce département est inconnu.');
		}

		if (null !== $minister) {
			$minister->status = PlayerStatus::Parliament;
		}
		$appointee->status = $department;

		$entityManager->flush();

		$eventDispatcher->dispatch(new NewMinisterEvent(
			$currentPlayer,
			$appointee,
			$minister,
		));

		$this->addFlash('success', $appointee->name.' a rejoint votre gouvernement.');

		return $this->redirect($request->headers->get('referer'));
	}
}
