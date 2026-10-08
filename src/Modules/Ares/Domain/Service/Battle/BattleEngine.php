<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleSquadron;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\TurnResult;
use App\Modules\Ares\Domain\Service\Battle\Engine\DeployFleets;
use App\Modules\Ares\Domain\Service\Battle\Engine\MoveFleets;
use App\Modules\Ares\Domain\Service\Battle\Engine\MoveSquadrons;
use App\Modules\Ares\Domain\Service\Battle\Engine\ResolveFire;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Resolves a battle turn by turn (see docs/fleet-combat/plan.md).
 *
 * A turn is: arrival of fleets, fleet movement, squadron movement, then fire in initiative order.
 * The engine is deterministic for a given battle seed, so a battle can be replayed exactly.
 */
readonly class BattleEngine
{
	public function __construct(
		private DeployFleets $deployFleets = new DeployFleets(),
		private MoveFleets $moveFleets = new MoveFleets(),
		private MoveSquadrons $moveSquadrons = new MoveSquadrons(),
		private ResolveFire $resolveFire = new ResolveFire(),
	) {
	}

	public function resolveTurn(BattleState $state): TurnResult
	{
		if ($state->isOver) {
			throw new \LogicException('The battle is already over');
		}

		++$state->turn;
		$randomizer = new Randomizer(new Mt19937($this->getTurnSeed($state)));

		$events = ($this->deployFleets)($state);
		$events = [...$events, ...($this->moveFleets)($state, $randomizer)];
		$squadrons = $this->sortByInitiative($state->getActiveSquadrons(), $randomizer);
		$events = [...$events, ...($this->moveSquadrons)($state, $squadrons, $randomizer)];
		$events = [...$events, ...($this->resolveFire)($state, $squadrons, $randomizer)];

		// Including fleets destroyed on the turn they arrived
		foreach ($state->getDeployedFleets() as $fleet) {
			if ($fleet->isDestroyed() && !$fleet->isDestructionReported) {
				$fleet->isDestructionReported = true;
				$events[] = BattleEvent::fleetDestroyed($fleet);
			}
		}

		$remainingSides = $state->getRemainingSides();
		if (count($remainingSides) <= 1 || $state->turn >= $state->maxTurns) {
			$state->isOver = true;
			$state->winningSide = 1 === count($remainingSides) ? $remainingSides[0] : null;
			$events[] = BattleEvent::battleEnded($state->winningSide);
		}

		return new TurnResult($state->turn, $events);
	}

	/**
	 * @return list<TurnResult>
	 */
	public function resolveBattle(BattleState $state): array
	{
		$turns = [];

		while (!$state->isOver) {
			$turns[] = $this->resolveTurn($state);
		}

		return $turns;
	}

	/**
	 * Highest initiative first, ties broken at random.
	 *
	 * @param list<BattleSquadron> $squadrons
	 *
	 * @return list<BattleSquadron>
	 */
	private function sortByInitiative(array $squadrons, Randomizer $randomizer): array
	{
		$squadrons = $randomizer->shuffleArray($squadrons);
		usort($squadrons, fn (BattleSquadron $a, BattleSquadron $b) => $b->type->initiative <=> $a->type->initiative);

		return $squadrons;
	}

	private function getTurnSeed(BattleState $state): int
	{
		return ($state->seed + $state->turn * 7919) & 0x7FFFFFFF;
	}
}
