<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Controller\CombatSimulator;

use App\Modules\Ares\Application\Battle\BattleShipTypeRegistry;
use App\Modules\Ares\Application\Battle\CreateBattle;
use App\Modules\Ares\Application\Battle\GetBattleShipTypeName;
use App\Modules\Ares\Domain\Model\Battle\TurnResult;
use App\Modules\Ares\Domain\Service\Battle\BattleEngine;
use App\Modules\Ares\Domain\Service\Battle\ComputeBattleExperience;
use App\Modules\Portal\Infrastructure\CombatSimulator\SimulatedFleetStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
		ComputeBattleExperience $computeBattleExperience,
		BattleShipTypeRegistry $battleShipTypeRegistry,
		GetBattleShipTypeName $getBattleShipTypeName,
		TranslatorInterface $translator,
		#[Autowire('%ares.battle.turn_duration%')]
		int $turnDuration,
	): JsonResponse {
		$fleets = $simulatedFleetStorage->all();
		$sides = array_unique(array_column($fleets, 'side'));

		if (count($sides) < 2) {
			return new JsonResponse(['error' => 'Chaque camp doit avoir au moins une flotte.'], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$payload = json_decode($request->getContent() ?: '{}', true);
		$seed = is_int($payload['seed'] ?? null) ? $payload['seed'] : random_int(1, 1_000_000);

		try {
			$state = $createBattle(array_map(fn (array $fleet) => [
				'identifier' => $fleet['id'],
				'name' => $fleet['name'],
				'side' => $fleet['side'],
				'faction' => $fleet['faction'],
				'officer_level' => $fleet['officer_level'],
				'arrival_turn' => $fleet['arrival_turn'],
				'formation' => $fleet['formation'],
			], $fleets), $seed);
		} catch (\DomainException $exception) {
			return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$turns = $battleEngine->resolveBattle($state);

		$shipTypes = [];
		foreach ($battleShipTypeRegistry->all() as $identifier => $type) {
			$shipTypes[$identifier] = [
				'name' => $getBattleShipTypeName($type),
				'role' => $type->role->value,
				'class' => $type->class->value,
				'pev' => $type->pev,
			];
		}

		return new JsonResponse([
			'seed' => $seed,
			'turn_duration' => $turnDuration,
			'grid' => [
				'cluster_radius' => $state->grid->clusterRadius,
				'battlefield_size' => $state->battlefieldSize,
			],
			'fleets' => array_map(fn (array $fleet) => [
				'id' => $fleet['id'],
				'name' => $fleet['name'],
				'side' => $fleet['side'],
				'faction' => $fleet['faction'],
				'faction_name' => 0 === $fleet['faction'] ? 'Sans faction' : $translator->trans(sprintf('factions.%d.name.official', $fleet['faction'])),
				'officer_level' => $fleet['officer_level'],
				'arrival_turn' => $fleet['arrival_turn'],
			], $fleets),
			'squadron_life' => array_merge([], ...array_map(
				fn ($fleet) => array_combine(
					array_map(fn ($squadron) => $squadron->identifier, $fleet->squadrons),
					array_map(fn ($squadron) => $squadron->maxShipLife, $fleet->squadrons),
				),
				$state->getDeployedFleets(),
			)),
			'ship_types' => $shipTypes,
			'turns' => array_map(fn (TurnResult $turn) => $turn->toArray(), $turns),
			'result' => [
				'winning_side' => $state->winningSide,
				'turns' => $state->turn,
				'experience' => $computeBattleExperience($state),
			],
		]);
	}
}
