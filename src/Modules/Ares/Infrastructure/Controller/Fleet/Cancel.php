<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class Cancel extends AbstractController
{
	public function __invoke(
		Request $request,
		DurationHandler $durationHandler,
		MessageBusInterface $messageBus,
		Player $currentPlayer,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		Uuid $id,
	): Response {
		$commander = $commanderRepository->get($id) ?? throw $this->createNotFoundException('Commander not found');
		// TODO Voter
		if ($commander->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('Ce commandant ne vous appartient pas ou n\'existe pas.');
		}
		if ($commander->isComingBack()) {
			throw new ConflictHttpException('Vous ne pouvez pas annuler un retour.');
		}
		// @TODO travel cancellation
		// $scheduler->cancel($commander, $commander->dArrival);

		$interval = $durationHandler->getDiff($commander->getDepartureDate(), new \DateTimeImmutable());
		$newArrivalDate = new \DateTimeImmutable(sprintf('-%d seconds', $interval));

		$rDestinationPlace = $commander->destinationPlace;
		$commander->destinationPlace = $commander->startPlace;
		$commander->startPlace = $rDestinationPlace;
		$commander->departedAt = new \DateTimeImmutable();
		$commander->arrivedAt = $newArrivalDate;
		$commander->travelType = CommanderMission::Back;

		$this->addFlash('success', 'Déplacement annulé.');

		$messageBus->dispatch(
			new \App\Modules\Ares\Message\CommanderTravelMessage($commander->id),
			[\App\Classes\Library\DateTimeConverter::to_delay_stamp($commander->getArrivalDate())],
		);
		$commanderRepository->save($commander);

		if ($request->query->has('redirect')) {
			return $this->redirectToRoute('map', ['place' => $request->query->get('redirect')]);
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
