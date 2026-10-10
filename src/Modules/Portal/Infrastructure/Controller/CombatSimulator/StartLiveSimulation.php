<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Controller\CombatSimulator;

use App\Modules\Ares\Application\Battle\CreateBattle;
use App\Modules\Ares\Application\Battle\StartBattle;
use App\Modules\Portal\Infrastructure\CombatSimulator\PresentLiveBattle;
use App\Modules\Portal\Infrastructure\CombatSimulator\SimulatedBattleStorage;
use App\Modules\Portal\Infrastructure\CombatSimulator\SimulatedFleetStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Starts a real-time battle between the simulated fleets (Phase 6): the server resolves a turn
 * every BATTLE_TURN_DURATION seconds and pushes it to the browser.
 */
#[Route(
	path: '/combat-simulator/live',
	name: 'combat_simulator_live_start',
	methods: [Request::METHOD_POST],
)]
class StartLiveSimulation extends AbstractController
{
	public function __invoke(
		Request $request,
		SimulatedFleetStorage $simulatedFleetStorage,
		SimulatedBattleStorage $simulatedBattleStorage,
		CreateBattle $createBattle,
		StartBattle $startBattle,
		PresentLiveBattle $presentLiveBattle,
	): JsonResponse {
		$fleets = $simulatedFleetStorage->all();

		if (count(array_unique(array_column($fleets, 'side'))) < 2) {
			return new JsonResponse(['error' => 'Chaque camp doit avoir au moins une flotte.'], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$payload = json_decode($request->getContent() ?: '{}', true);
		$seed = is_int($payload['seed'] ?? null) ? $payload['seed'] : random_int(1, 1_000_000);

		try {
			$state = $createBattle(array_map(SimulatedFleetStorage::toBattleFleet(...), $fleets), $seed);
		} catch (\DomainException $exception) {
			return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$battle = $startBattle($state);
		$simulatedBattleStorage->add($battle->id);

		return $presentLiveBattle($request, $battle);
	}
}
