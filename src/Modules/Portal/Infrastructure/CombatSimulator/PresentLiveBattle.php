<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\CombatSimulator;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Infrastructure\Battle\PresentStoredBattle;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * A real-time battle of the simulator, with the simulated fleets that can be sent as reinforcements.
 */
readonly class PresentLiveBattle
{
	public function __construct(
		private PresentStoredBattle $presentStoredBattle,
		private SimulatedFleetStorage $simulatedFleetStorage,
	) {
	}

	public function __invoke(Request $request, Battle $battle): JsonResponse
	{
		return new JsonResponse(($this->presentStoredBattle)($request, $battle, [
			'reinforcements' => array_map(fn (array $fleet) => [
				'id' => $fleet['id'],
				'name' => $fleet['name'],
				'side' => $fleet['side'],
			], $this->simulatedFleetStorage->all()),
		]));
	}
}
