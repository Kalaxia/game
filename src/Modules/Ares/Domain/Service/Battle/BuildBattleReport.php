<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleShipType;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\Report\BattleReport;
use App\Modules\Ares\Domain\Model\Battle\Report\SquadronReport;
use App\Modules\Ares\Domain\Model\Battle\TurnResult;

/**
 * Builds the post-battle report from the event log (Q16).
 *
 * The figures only come from the events, so that a report can be rebuilt from a stored battle log.
 * The battle state gives the ship types and the remaining ships.
 *
 * Key moments: first blood, deadliest action, most valuable squadron, reinforcements, destroyed fleets,
 * engagement delay, withdrawals and flights (D11, D12), and the turning point, i.e. the turn from which the winning side kept the lead in remaining PEV
 * after another side had led.
 */
readonly class BuildBattleReport
{
	/**
	 * @param list<TurnResult>   $turns
	 * @param array<string, int> $experience officer experience by fleet identifier
	 */
	public function __invoke(BattleState $state, array $turns, array $experience = []): BattleReport
	{
		$types = $this->getSquadronTypes($state);
		$fleetsById = [];
		foreach ($state->fleets as $fleet) {
			$fleetsById[$fleet->identifier] = $fleet;
		}

		/** @var array<string, SquadronReport> $squadrons */
		$squadrons = [];
		$joinedAt = [];
		$destroyedAt = [];
		$fledAt = [];
		$morale = [];
		$killsByType = [];
		$highlights = [];
		$deadliestAction = null;
		$firstBlood = null;
		$sideInitialPev = [];
		$sideLostPev = [];
		$leaders = [];

		foreach ($turns as $turn) {
			foreach ($turn->events as $event) {
				$payload = $event->payload;

				switch ($event->type) {
					case BattleEvent::FLEET_JOINED:
						$joinedAt[$payload['fleet']] = $turn->turn;
						foreach ($payload['squadrons'] as $squadron) {
							$squadrons[$squadron['squadron']] = new SquadronReport(
								squadron: $squadron['squadron'],
								fleet: $payload['fleet'],
								type: $squadron['type'],
								initialShips: $squadron['ships'],
								remainingShips: $squadron['ships'],
							);
							$sideInitialPev[$payload['side']] = ($sideInitialPev[$payload['side']] ?? 0) + $squadron['ships'] * $types[$squadron['squadron']]->pev;
						}
						if ($turn->turn > 1) {
							$highlights[] = ['type' => BattleReport::HIGHLIGHT_REINFORCEMENT, 'turn' => $turn->turn, 'fleet' => $payload['fleet']];
						}
						break;

					case BattleEvent::SQUADRON_FIRED:
						$attacker = $squadrons[$payload['attacker']];
						$target = $squadrons[$payload['target']];
						$attacker->damageDealt += $payload['damage'];
						$attacker->kills += $payload['kills'];
						$attacker->shots += $payload['shots'];
						$attacker->hits += $payload['hits'];
						$target->damageTaken += $payload['damage'];
						$target->remainingShips -= $payload['kills'];

						$killsByType[$attacker->fleet][$target->type] = ($killsByType[$attacker->fleet][$target->type] ?? 0) + $payload['kills'];
						$targetSide = $fleetsById[$target->fleet]->side;
						$sideLostPev[$targetSide] = ($sideLostPev[$targetSide] ?? 0) + $payload['kills'] * $types[$target->squadron]->pev;

						if (null === $firstBlood && $payload['kills'] > 0) {
							$firstBlood = ['type' => BattleReport::HIGHLIGHT_FIRST_BLOOD, 'turn' => $turn->turn, 'attacker' => $attacker->squadron, 'target' => $target->squadron, 'kills' => $payload['kills']];
						}
						if (null === $deadliestAction || $payload['damage'] > $deadliestAction['damage']) {
							$deadliestAction = ['type' => BattleReport::HIGHLIGHT_DEADLIEST_ACTION, 'turn' => $turn->turn, 'attacker' => $attacker->squadron, 'target' => $target->squadron, 'damage' => $payload['damage'], 'kills' => $payload['kills']];
						}
						break;

					case BattleEvent::SQUADRON_SPLASHED:
						$attacker = $squadrons[$payload['attacker']];
						$victim = $squadrons[$payload['target']];
						$victim->damageTaken += $payload['damage'];
						$victim->remainingShips -= $payload['kills'];
						$victimSide = $fleetsById[$victim->fleet]->side;
						$sideLostPev[$victimSide] = ($sideLostPev[$victimSide] ?? 0) + $payload['kills'] * $types[$victim->squadron]->pev;

						// Area damage on allies brings nothing to the shooter
						if ($victimSide !== $fleetsById[$attacker->fleet]->side) {
							$attacker->damageDealt += $payload['damage'];
							$attacker->kills += $payload['kills'];
							$killsByType[$attacker->fleet][$victim->type] = ($killsByType[$attacker->fleet][$victim->type] ?? 0) + $payload['kills'];
						}
						break;

					case BattleEvent::SQUADRON_DESTROYED:
						$squadrons[$payload['squadron']]->destroyedAtTurn = $turn->turn;
						break;

					case BattleEvent::ENGAGEMENT_DELAYED:
						$highlights[] = ['type' => BattleReport::HIGHLIGHT_ENGAGEMENT_DELAYED, 'turn' => $turn->turn, 'until_turn' => $payload['until_turn']];
						break;

					case BattleEvent::FLEET_WITHDREW:
						$highlights[] = ['type' => BattleReport::HIGHLIGHT_FLEET_WITHDREW, 'turn' => $turn->turn, 'fleet' => $payload['fleet']];
						break;

					case BattleEvent::FLEET_FLED:
						$fledAt[$payload['fleet']] = $turn->turn;
						$highlights[] = ['type' => BattleReport::HIGHLIGHT_FLEET_FLED, 'turn' => $turn->turn, 'fleet' => $payload['fleet']];
						break;

					case BattleEvent::MORALE_UPDATED:
						$morale = $payload['morale'] + $morale;
						break;

					case BattleEvent::FLEET_DESTROYED:
						$destroyedAt[$payload['fleet']] = $turn->turn;
						$highlights[] = ['type' => BattleReport::HIGHLIGHT_FLEET_DESTROYED, 'turn' => $turn->turn, 'fleet' => $payload['fleet']];
						break;
				}
			}

			$leaders[$turn->turn] = $this->getLeader($sideInitialPev, $sideLostPev);
		}

		foreach ([$firstBlood, $deadliestAction] as $highlight) {
			if (null !== $highlight) {
				$highlights[] = $highlight;
			}
		}
		if (null !== ($turningPoint = $this->findTurningPoint($leaders, $state->winningSide))) {
			$highlights[] = $turningPoint;
		}
		if (null !== ($mostValuable = $this->findMostValuableSquadron($squadrons))) {
			$highlights[] = ['type' => BattleReport::HIGHLIGHT_MOST_VALUABLE_SQUADRON, 'turn' => $mostValuable->destroyedAtTurn ?? $state->turn, 'squadron' => $mostValuable->squadron, 'damage' => $mostValuable->damageDealt, 'kills' => $mostValuable->kills];
		}
		usort($highlights, fn (array $a, array $b) => $a['turn'] <=> $b['turn']);

		return new BattleReport(
			turns: $state->turn,
			winningSide: $state->winningSide,
			sides: $this->summarizeSides($state, $squadrons, $sideInitialPev, $sideLostPev),
			fleets: array_map(
				fn (BattleFleet $fleet) => [
					...$this->summarizeFleet($fleet, $squadrons, $types, $killsByType[$fleet->identifier] ?? [], $joinedAt[$fleet->identifier] ?? null, $destroyedAt[$fleet->identifier] ?? null, $experience[$fleet->identifier] ?? 0),
					'fled_at_turn' => $fledAt[$fleet->identifier] ?? null,
					'final_status' => $fleet->status->value,
					'final_morale' => $morale[$fleet->identifier] ?? null,
					'traits' => array_map(fn ($trait) => $trait->value, $fleet->traits),
				],
				$state->fleets,
			),
			squadrons: array_values($squadrons),
			highlights: $highlights,
		);
	}

	/**
	 * @return array<string, BattleShipType> ship type by squadron identifier
	 */
	private function getSquadronTypes(BattleState $state): array
	{
		$types = [];
		foreach ($state->fleets as $fleet) {
			foreach ($fleet->squadrons as $squadron) {
				$types[$squadron->identifier] = $squadron->type;
			}
		}

		return $types;
	}

	/**
	 * Side with the most remaining PEV, null on a tie or when fewer than two sides are engaged.
	 *
	 * @param array<int, int> $initialPev
	 * @param array<int, int> $lostPev
	 */
	private function getLeader(array $initialPev, array $lostPev): ?int
	{
		if (count($initialPev) < 2) {
			return null;
		}

		$remaining = [];
		foreach ($initialPev as $side => $pev) {
			$remaining[$side] = $pev - ($lostPev[$side] ?? 0);
		}
		arsort($remaining);
		[$first, $second] = array_values($remaining);

		return $first === $second ? null : array_key_first($remaining);
	}

	/**
	 * @param array<int, int|null> $leaders leading side after each turn
	 *
	 * @return array{type: string, turn: int, side: int}|null
	 */
	private function findTurningPoint(array $leaders, ?int $winningSide): ?array
	{
		if (null === $winningSide) {
			return null;
		}

		$turningPoint = null;
		$anotherSideLed = false;
		$previousLeader = null;

		foreach ($leaders as $turn => $leader) {
			if (null !== $leader && $leader !== $winningSide) {
				$anotherSideLed = true;
			}
			if ($leader === $winningSide && $previousLeader !== $winningSide) {
				$turningPoint = $turn;
			}
			if (null !== $leader) {
				$previousLeader = $leader;
			}
		}

		return $anotherSideLed && null !== $turningPoint
			? ['type' => BattleReport::HIGHLIGHT_TURNING_POINT, 'turn' => $turningPoint, 'side' => $winningSide]
			: null;
	}

	/**
	 * @param array<string, SquadronReport> $squadrons
	 */
	private function findMostValuableSquadron(array $squadrons): ?SquadronReport
	{
		$best = null;
		foreach ($squadrons as $squadron) {
			if ($squadron->damageDealt > ($best?->damageDealt ?? 0)) {
				$best = $squadron;
			}
		}

		return $best;
	}

	/**
	 * @param array<string, SquadronReport> $squadrons
	 * @param array<int, int>               $initialPev
	 * @param array<int, int>               $lostPev
	 *
	 * @return array<int, array{initial_pev: int, lost_pev: int, ships_lost: int, damage_dealt: int}>
	 */
	private function summarizeSides(BattleState $state, array $squadrons, array $initialPev, array $lostPev): array
	{
		$sides = [];
		foreach ($state->fleets as $fleet) {
			$sides[$fleet->side] ??= ['initial_pev' => $initialPev[$fleet->side] ?? 0, 'lost_pev' => $lostPev[$fleet->side] ?? 0, 'ships_lost' => 0, 'damage_dealt' => 0];
		}

		foreach ($squadrons as $squadron) {
			$side = $this->getFleetSide($state, $squadron->fleet);
			$sides[$side]['ships_lost'] += $squadron->initialShips - $squadron->remainingShips;
			$sides[$side]['damage_dealt'] += $squadron->damageDealt;
		}
		ksort($sides);

		return $sides;
	}

	/**
	 * @param array<string, SquadronReport> $squadrons
	 * @param array<string, BattleShipType> $types
	 * @param array<string, int>            $killsByType
	 *
	 * @return array<string, mixed>
	 */
	private function summarizeFleet(BattleFleet $fleet, array $squadrons, array $types, array $killsByType, ?int $joinedAt, ?int $destroyedAt, int $experience): array
	{
		$ships = [];
		$damageDealt = 0;
		$damageTaken = 0;
		$initialPev = 0;
		$remainingPev = 0;

		foreach ($squadrons as $squadron) {
			if ($squadron->fleet !== $fleet->identifier) {
				continue;
			}

			$pev = $types[$squadron->squadron]->pev;
			$ships[$squadron->type] ??= ['initial' => 0, 'remaining' => 0];
			$ships[$squadron->type]['initial'] += $squadron->initialShips;
			$ships[$squadron->type]['remaining'] += $squadron->remainingShips;
			$damageDealt += $squadron->damageDealt;
			$damageTaken += $squadron->damageTaken;
			$initialPev += $squadron->initialShips * $pev;
			$remainingPev += $squadron->remainingShips * $pev;
		}

		return [
			'fleet' => $fleet->identifier,
			'side' => $fleet->side,
			'faction' => $fleet->faction,
			'joined_at_turn' => $joinedAt,
			'destroyed_at_turn' => $destroyedAt,
			'ships' => $ships,
			'initial_pev' => $initialPev,
			'remaining_pev' => $remainingPev,
			'damage_dealt' => $damageDealt,
			'damage_taken' => $damageTaken,
			'kills_by_type' => $killsByType,
			'experience' => $experience,
			'bonuses' => $fleet->bonuses->toArray(),
		];
	}

	private function getFleetSide(BattleState $state, string $fleetId): int
	{
		foreach ($state->fleets as $fleet) {
			if ($fleet->identifier === $fleetId) {
				return $fleet->side;
			}
		}

		throw new \LogicException(sprintf('Unknown fleet %s', $fleetId));
	}
}
