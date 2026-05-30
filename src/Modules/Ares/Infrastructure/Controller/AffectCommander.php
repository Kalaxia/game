<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Modules\Ares\Domain\Event\Commander\AffectationEvent;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Galaxy\Resource\PlaceResource;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

class AffectCommander extends AbstractController
{
	#[Route(
		path: '/commanders/{id}/affect',
		name: 'affect_commander',
		requirements: [
			'id' => Requirement::UUID_V4,
		],
		methods: Request::METHOD_GET,
	)]
	public function __invoke(
		Uuid $id,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		EventDispatcherInterface $eventDispatcher,
		Player $currentPlayer,
	): Response {
		$commander = $commanderRepository->get($id)
			?? throw $this->createNotFoundException('Cet officier n\'existe pas ou ne vous appartient pas');

		$planet = $commander->base;

		if ($commander->isInSchool() || $commander->isInReserve()) {
			$commander->assignedAt = new \DateTimeImmutable();
			$commander->statement = Commander::AFFECTED;

			$commanderRepository->save($commander);

			$eventDispatcher->dispatch(new AffectationEvent($commander, $currentPlayer));

			$this->addFlash('success', sprintf('Votre officier %s a bien été affecté en orbite', $commander->name));

			return $this->redirectToRoute('fleet_headquarters', ['commander' => $commander->id]);
		} elseif ($commander->isAffected()) {
			$baseCommanders = $commanderRepository->getPlanetCommanders(
				$planet,
				[Commander::INSCHOOL],
			);

			$commander->updatedAt = new \DateTimeImmutable();
			if (count($baseCommanders) < PlaceResource::get($planet->typeOfBase, 'school-size')) {
				$commander->statement = Commander::INSCHOOL;
				$this->addFlash('success', 'Votre officier '.$commander->name.' a été remis à l\'école');
			} else {
				$commander->statement = Commander::RESERVE;
				$this->addFlash('success', 'Votre officier '.$commander->name.' a été remis dans la réserve de l\'armée');
			}
			$commanderManager->emptySquadrons($commander);
			$commanderRepository->save($commander);

			return $this->redirectToRoute('school');
		}
		throw new ConflictHttpException('Le statut de votre officier ne peut pas être modifié');
	}
}
