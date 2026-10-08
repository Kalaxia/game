<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Controller\CombatSimulator;

use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Portal\Infrastructure\CombatSimulator\PresentLiveBattle;
use App\Modules\Portal\Infrastructure\CombatSimulator\SimulatedBattleStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * A real-time battle of the visitor up to its last resolved turn: used to catch up after
 * (re)connecting to the live updates, and to get the report once it is over.
 */
#[Route(
	path: '/combat-simulator/live/{id}',
	name: 'combat_simulator_live_view',
	requirements: ['id' => '[0-9a-f-]{36}'],
	methods: [Request::METHOD_GET],
)]
class ViewLiveSimulation extends AbstractController
{
	public function __invoke(
		Request $request,
		Uuid $id,
		BattleRepositoryInterface $battleRepository,
		SimulatedBattleStorage $simulatedBattleStorage,
		PresentLiveBattle $presentLiveBattle,
	): JsonResponse {
		if (!$simulatedBattleStorage->has($id) || null === ($battle = $battleRepository->get($id))) {
			return new JsonResponse(['error' => 'Bataille introuvable.'], Response::HTTP_NOT_FOUND);
		}

		return $presentLiveBattle($request, $battle);
	}
}
