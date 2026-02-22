<?php

namespace App\Modules\Ares\Infrastructure\Controller\Commander;

use App\Classes\Library\Parser;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class UpdateName extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		Parser $parser,
		Uuid $id,
	): Response {
		$commander = $commanderRepository->get($id) ?? throw $this->createNotFoundException('Commander not found');
		// TODO Voter
		if ($commander->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('Ce commandant ne vous appartient pas');
		}
		$name = $parser->protect($request->request->get('name'));
		$nameLength = strlen($name);
		if ($nameLength < 2 || $nameLength > 25) {
			throw new BadRequestHttpException('le nom doit comporter entre 2 et 25 caractères');
		}

		$commander->name = $name;

		$commanderRepository->save($commander);

		$this->addFlash('success', 'le nom de votre commandant est maintenant '.$name);

		return $this->redirect($request->headers->get('referer'));
	}
}
