<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleBonuses;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\FormationSlot;
use App\Modules\Ares\Domain\Model\Battle\Grid\ClusteredHexGrid;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Modules\Ares\Domain\Model\Battle\OfficerTrait;
use App\Modules\Ares\Domain\Model\Battle\Rules\BattleRules;
use App\Modules\Ares\Domain\Model\Battle\ShipClass;
use App\Modules\Ares\Domain\Service\Battle\BuildFleetFormation;
use App\Modules\Ares\Domain\Service\Battle\ValidateFormation;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Builds a battle from plain fleet descriptions, as used by the combat simulator and the balance harness.
 */
readonly class CreateBattle
{
	private BuildFleetFormation $buildFleetFormation;
	private ?int $maxActionsPerTurn;
	private ValidateFormation $validateFormation;

	public function __construct(
		private BattleShipTypeRegistry $battleShipTypeRegistry,
		private GetFactionBattleBonuses $getFactionBattleBonuses,
		#[Autowire('%ares.battle.battlefield_size%')]
		private int $battlefieldSize,
		#[Autowire('%ares.battle.fleet_area_radius%')]
		private int $fleetAreaRadius,
		#[Autowire('%ares.battle.formation_radius%')]
		int $formationRadius,
		#[Autowire('%ares.battle.durability_factor%')]
		private float $durabilityFactor = 1.0,
		#[Autowire('%ares.battle.turn_duration%')]
		int $turnDuration = 10,
		#[Autowire('%ares.battle.action_duration%')]
		?float $actionDuration = null,
		/** Fleet-size maluses, engagement delay and morale (D10 to D12); none when null */
		private ?GetBattleRules $getBattleRules = null,
	) {
		// As many actions as can be animated one after another during the real duration of a turn
		$this->maxActionsPerTurn = null !== $actionDuration ? max(1, (int) floor($turnDuration / $actionDuration)) : null;
		$this->buildFleetFormation = new BuildFleetFormation($formationRadius);
		$this->validateFormation = new ValidateFormation($formationRadius);
	}

	/**
	 * A fleet is given either by its formation, composed by the player (D9): a list of squadrons
	 * [type, ships, q, r] with the front facing east, or by its ships only, arranged automatically.
	 *
	 * Each fleet gets the combat bonuses of its faction, plus optional extra bonuses (e.g. technologies),
	 * given as percentages by ship class value: [class => ['attack' => 10, 'defense' => 5, 'speed' => 0]].
	 *
	 * @param list<array{identifier?: string, name: string, side: int, faction: int, officer_level?: int, arrival_turn?: int, traits?: list<string>, formation?: list<array{type: string, ships: int, q: int, r: int}>, ships?: array<string, int>, bonuses?: array<int, array{attack?: int, defense?: int, speed?: int}>}> $fleets
	 *
	 * @throws \DomainException when a formation is invalid
	 */
	public function __invoke(array $fleets, int $seed): BattleState
	{
		return new BattleState(
			grid: new ClusteredHexGrid($this->fleetAreaRadius),
			battlefieldSize: $this->battlefieldSize,
			seed: $seed,
			fleets: array_map($this->createFleet(...), $fleets, array_keys($fleets)),
			durabilityFactor: $this->durabilityFactor,
			maxActionsPerTurn: $this->maxActionsPerTurn,
			// In the simulator, side A is in orbit and side B attacks
			rules: null !== $this->getBattleRules ? ($this->getBattleRules)(defendingSide: 0) : new BattleRules(),
		);
	}

	/**
	 * Builds one fleet, given as described in __invoke(). Also used for a fleet joining a running battle.
	 *
	 * @param array{identifier?: string, name: string, side: int, faction: int, officer_level?: int, arrival_turn?: int, traits?: list<string>, formation?: list<array{type: string, ships: int, q: int, r: int}>, ships?: array<string, int>, bonuses?: array<int, array{attack?: int, defense?: int, speed?: int}>} $fleet
	 *
	 * @throws \DomainException when the formation is invalid
	 */
	public function createFleet(array $fleet, int $index = 0): BattleFleet
	{
		return new BattleFleet(
			identifier: $fleet['identifier'] ?? sprintf('fleet-%d', $index),
			name: $fleet['name'],
			faction: $fleet['faction'],
			side: $fleet['side'],
			officerLevel: $fleet['officer_level'] ?? 1,
			formation: $this->getFormation($fleet),
			arrivalTurn: $fleet['arrival_turn'] ?? 1,
			bonuses: $this->getBonuses($fleet),
			traits: array_map(fn (string $trait) => OfficerTrait::from($trait), $fleet['traits'] ?? []),
		);
	}

	/**
	 * @param array{name: string, formation?: list<array{type: string, ships: int, q: int, r: int}>, ships?: array<string, int>} $fleet
	 *
	 * @return list<FormationSlot>
	 */
	private function getFormation(array $fleet): array
	{
		if (!isset($fleet['formation'])) {
			return ($this->buildFleetFormation)(array_map(
				fn (string $type, int $quantity) => [$this->battleShipTypeRegistry->get($type), $quantity],
				array_keys($fleet['ships'] ?? []),
				array_values($fleet['ships'] ?? []),
			));
		}

		$formation = array_map(
			fn (array $slot) => new FormationSlot($this->battleShipTypeRegistry->get($slot['type']), $slot['ships'], new Hex($slot['q'], $slot['r'])),
			$fleet['formation'],
		);

		if ([] !== ($violations = ($this->validateFormation)($formation))) {
			throw new \DomainException(sprintf('Invalid formation for fleet %s: %s', $fleet['name'], implode('; ', array_map('strval', $violations))));
		}

		return $formation;
	}

	/**
	 * @param array{faction: int, faction_bonuses?: bool, bonuses?: array<int, array{attack?: int, defense?: int, speed?: int}>} $fleet
	 */
	private function getBonuses(array $fleet): BattleBonuses
	{
		// A player's bonuses already include those of their faction
		$bonuses = ($fleet['faction_bonuses'] ?? true) ? ($this->getFactionBattleBonuses)($fleet['faction']) : new BattleBonuses();

		foreach ($fleet['bonuses'] ?? [] as $class => $percentages) {
			$bonuses = $bonuses->add(ShipClass::from($class), ...$percentages);
		}

		return $bonuses;
	}
}
