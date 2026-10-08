<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Controller\CombatSimulator;

use App\Modules\Ares\Application\Battle\CreateBattle;
use App\Modules\Ares\Domain\Service\Battle\BattleEngine;
use App\Modules\Ares\Infrastructure\Battle\PresentBattle;
use App\Modules\Portal\Infrastructure\CombatSimulator\SimulatedFleetStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Computes a whole battle between the simulated fleets and returns its event log, replayed by the browser.
 */
#[Route(
	path: '/combat-simulator/run',
	name: 'combat_simulator_run',
	methods: [Request::METHOD_POST],
)]
class RunSimulation extends AbstractController
{
	public function __invoke(
		Request $request,
		SimulatedFleetStorage $simulatedFleetStorage,
		CreateBattle $createBattle,
		BattleEngine $battleEngine,
		PresentBattle $presentBattle,
		#[Autowire('%ares.battle.turn_duration%')]
		int $turnDuration,
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

		return new JsonResponse($presentBattle($state, $battleEngine->resolveBattle($state), $turnDuration));
	}
}
