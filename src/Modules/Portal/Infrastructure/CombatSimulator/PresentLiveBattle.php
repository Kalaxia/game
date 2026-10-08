<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\CombatSimulator;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\Battle\BattleTurn;
use App\Modules\Ares\Domain\Repository\BattleTurnRepositoryInterface;
use App\Modules\Ares\Infrastructure\Battle\MercureBattleUpdatePublisher;
use App\Modules\Ares\Infrastructure\Battle\PresentBattle;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\HubInterface;

/**
 * A real-time battle as seen by the viewer so far, with what it needs to follow the next turns:
 * the Mercure topic of the battle, and a cookie authorising the visitor to subscribe to it.
 */
readonly class PresentLiveBattle
{
	public function __construct(
		private BattleTurnRepositoryInterface $battleTurnRepository,
		private PresentBattle $presentBattle,
		private HubInterface $hub,
		private Authorization $authorization,
		private SimulatedFleetStorage $simulatedFleetStorage,
	) {
	}

	public function __invoke(Request $request, Battle $battle): JsonResponse
	{
		$topic = MercureBattleUpdatePublisher::getTopic($battle->id);
		$this->authorization->setCookie($request, [$topic]);

		$turns = array_map(fn (BattleTurn $turn) => $turn->toResult(), $this->battleTurnRepository->getBattleTurns($battle));

		return new JsonResponse([
			...($this->presentBattle)($battle->getState(), $turns, $battle->turnDuration),
			'live' => [
				'id' => $battle->id->toRfc4122(),
				'hub_url' => $this->hub->getPublicUrl(),
				'topic' => $topic,
				'is_over' => $battle->isOver(),
				// Fleets of the simulator that can be sent as reinforcements
				'reinforcements' => array_map(fn (array $fleet) => [
					'id' => $fleet['id'],
					'name' => $fleet['name'],
					'side' => $fleet['side'],
				], $this->simulatedFleetStorage->all()),
			],
		]);
	}
}
