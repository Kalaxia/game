<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Controller\CombatSimulator;

use App\Modules\Ares\Application\Battle\Message\JoinBattleMessage;
use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Portal\Infrastructure\CombatSimulator\SimulatedBattleStorage;
use App\Modules\Portal\Infrastructure\CombatSimulator\SimulatedFleetStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * Sends one of the simulated fleets into a running battle, as a fleet ending its travel would:
 * it reaches the battle after the given number of turns, then is deployed at the next turn (Q9).
 */
#[Route(
	path: '/combat-simulator/live/{id}/reinforcements',
	name: 'combat_simulator_live_reinforce',
	requirements: ['id' => '[0-9a-f-]{36}'],
	methods: [Request::METHOD_POST],
)]
class SendReinforcement extends AbstractController
{
	private const int MAX_TRAVEL_TURNS = 30;

	public function __invoke(
		Request $request,
		Uuid $id,
		BattleRepositoryInterface $battleRepository,
		SimulatedBattleStorage $simulatedBattleStorage,
		SimulatedFleetStorage $simulatedFleetStorage,
		MessageBusInterface $messageBus,
	): JsonResponse {
		if (!$simulatedBattleStorage->has($id) || null === ($battle = $battleRepository->get($id))) {
			return new JsonResponse(['error' => 'Bataille introuvable.'], Response::HTTP_NOT_FOUND);
		}

		if ($battle->isOver()) {
			return new JsonResponse(['error' => 'La bataille est terminée.'], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$payload = json_decode($request->getContent() ?: '{}', true);
		$fleet = $simulatedFleetStorage->get((string) ($payload['fleet'] ?? ''));
		$travelTurns = max(0, min(self::MAX_TRAVEL_TURNS, (int) ($payload['travel_turns'] ?? 0)));

		if (null === $fleet) {
			return new JsonResponse(['error' => 'Flotte introuvable.'], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		// The same fleet may be sent several times: each one is a new fleet of the battle
		$messageBus->dispatch(new JoinBattleMessage($id, [
			...SimulatedFleetStorage::toBattleFleet($fleet),
			'identifier' => sprintf('%s-%s', $fleet['id'], bin2hex(random_bytes(3))),
			'name' => sprintf('%s (renfort)', $fleet['name']),
		]), [new DelayStamp($travelTurns * $battle->turnDuration * 1000)]);

		return new JsonResponse(['travel_turns' => $travelTurns], Response::HTTP_ACCEPTED);
	}
}
