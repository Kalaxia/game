<?php

namespace App\Modules\Ares\Infrastructure\Controller\Commander;

use App\Modules\Ares\Domain\Event\Commander\LineChangeEvent;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Galaxy\Resource\PlaceResource;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

class ChangeLine extends AbstractController
{
	#[Route(
		path: '/commanders/{id}/change-line',
		name: 'change_commander_line',
		requirements: [
			'id' => Requirement::UUID_V4,
		],
		methods: Request::METHOD_GET,
	)]
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		EventDispatcherInterface $eventDispatcher,
		Uuid $id,
	): Response {
		$commander = $commanderRepository->get($id)
			?? throw $this->createNotFoundException('Ce commandant n\'existe pas');

		if ($commander->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('Ce commandant ne vous appartient pas');
		}

		$planet = $commander->base;

		// checker si on a assez de place !!!!!
		if (1 == $commander->line) {
			$secondLineCommanders = $commanderRepository->getCommandersByLine($planet, 2);

			$commander->line = 2;
			if (count($secondLineCommanders) >= PlaceResource::get($planet->typeOfBase, 'r-line')) {
				$secondLineCommanders[0]->line = 1;

				$this->addFlash('success', 'Votre commandant '.$commander->name.' a échangé sa place avec '.$commander->name.'.');
			}
		} else {
			$firstLineCommanders = $commanderRepository->getCommandersByLine($planet, 1);

			$commander->line = 1;
			if (count($firstLineCommanders) >= PlaceResource::get($planet->typeOfBase, 'l-line')) {
				$firstLineCommanders[0]->line = 2;
				$this->addFlash('success', 'Votre commandant '.$commander->name.' a échangé sa place avec '.$commander->name.'.');
			}
		}
		$commanderRepository->save($commander);

		$eventDispatcher->dispatch(new LineChangeEvent($commander, $currentPlayer));

		return $this->redirect($request->headers->get('referer'));
	}
}
