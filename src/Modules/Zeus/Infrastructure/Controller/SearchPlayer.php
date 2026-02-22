<?php

namespace App\Modules\Zeus\Infrastructure\Controller;

use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchPlayer extends AbstractController
{
	public function __construct(
		private readonly PlayerRepositoryInterface $playerRepository,
	) {
	}

	public function __invoke(Request $request): Response
	{
		$player = null;

		if (null !== ($id = $request->request->getInt('playerid'))) {
			$player = $this->searchById($id);
		}

		if (null !== ($name = $request->request->get('name'))) {
			$player = $this->searchByName($name);
		}

		if (null === $player) {
			throw $this->createNotFoundException('Aucun joueur ne correspond à votre recherche.');
		}

		return $this->redirectToRoute('embassy', ['player' => $player->id]);
	}

	private function searchById(int $id): ?Player
	{
		return $this->playerRepository->get($id);
	}

	private function searchByName(string $name): ?Player
	{
		return $this->playerRepository->getByName($name);
	}
}
