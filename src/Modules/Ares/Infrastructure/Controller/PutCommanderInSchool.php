<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Gaia\Resource\PlaceResource;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class PutCommanderInSchool extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CommanderRepositoryInterface $commanderRepository,
		Uuid $id
	): Response {
		// TODO Replace with Voter
		if (null === ($commander = $commanderRepository->get($id)) || $commander->player->id !== $currentPlayer->id) {
			throw new BadRequestHttpException('Ce commandant n\'existe pas ou ne vous appartient pas');
		}
		$planet = $commander->base;

		if ($commander->isInReserve()) {
			$commanders = $commanderRepository->getPlanetCommanders($commander->base, [Commander::INSCHOOL]);

			if (count($commanders) >= PlaceResource::get($planet->typeOfBase, 'school-size')) {
				throw new ConflictHttpException('Votre école est déjà pleine.');
			}
			$commander->statement = Commander::INSCHOOL;
		} elseif ($commander->isInSchool()) {
			$commander->statement = Commander::RESERVE;
		} else {
			throw new ConflictHttpException('Vous ne pouvez rien faire avec cet officier.');
		}
		$commander->updatedAt = new \DateTimeImmutable();

		$commanderRepository->save($commander);

		return $this->redirectToRoute('school');
	}
}
