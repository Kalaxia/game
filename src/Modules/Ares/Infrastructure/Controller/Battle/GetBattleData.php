<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Controller\Battle;

use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Ares\Infrastructure\Battle\CanWatchBattle;
use App\Modules\Ares\Infrastructure\Battle\PresentStoredBattle;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * Data of the battle viewer: the battle so far, the report once it is over.
 */
#[Route(
	path: '/battles/{id}/data',
	name: 'battle_data',
	requirements: ['id' => '[0-9a-f-]{36}'],
	methods: ['GET'],
)]
class GetBattleData extends AbstractController
{
	public function __invoke(
		Request $request,
		Uuid $id,
		Player $currentPlayer,
		BattleRepositoryInterface $battleRepository,
		CanWatchBattle $canWatchBattle,
		PresentStoredBattle $presentStoredBattle,
	): JsonResponse {
		$battle = $battleRepository->get($id);

		if (null === $battle || !$canWatchBattle($battle, $currentPlayer)) {
			return new JsonResponse(['error' => 'Bataille introuvable.'], Response::HTTP_NOT_FOUND);
		}

		return new JsonResponse($presentStoredBattle($request, $battle));
	}
}
