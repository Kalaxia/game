<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Battle;

use App\Modules\Ares\Application\Battle\BattleShipTypeRegistry;
use App\Modules\Ares\Application\Battle\GetBattleShipTypeName;
use App\Modules\Ares\Domain\Model\Battle\BattleEvent;
use App\Modules\Ares\Domain\Model\Battle\BattleFleet;
use App\Modules\Ares\Domain\Model\Battle\BattleState;
use App\Modules\Ares\Domain\Model\Battle\OfficerTrait;
use App\Modules\Ares\Domain\Model\Battle\TurnResult;
use App\Modules\Ares\Domain\Service\Battle\BuildBattleReport;
use App\Modules\Ares\Domain\Service\Battle\ComputeBattleExperience;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Data read by the battle viewer (replay_controller.js): the whole battle for a replay,
 * or one turn for the live viewer.
 */
readonly class PresentBattle
{
	public function __construct(
		private BattleShipTypeRegistry $battleShipTypeRegistry,
		private GetBattleShipTypeName $getBattleShipTypeName,
		private ComputeBattleExperience $computeBattleExperience,
		private BuildBattleReport $buildBattleReport,
		private TranslatorInterface $translator,
	) {
	}

	/**
	 * The battle up to its last resolved turn. The result and the report are only given once it is over.
	 *
	 * @param list<TurnResult> $turns
	 *
	 * @return array<string, mixed>
	 */
	public function __invoke(BattleState $state, array $turns, int $turnDuration): array
	{
		return [
			'seed' => $state->seed,
			'turn_duration' => $turnDuration,
			'grid' => [
				'cluster_radius' => $state->grid->clusterRadius,
				'battlefield_size' => $state->battlefieldSize,
			],
			// Including the fleets expected later, shown as arriving
			'fleets' => array_map($this->presentFleet(...), $state->fleets),
			'squadron_life' => $this->getSquadronLife($state->getDeployedFleets()),
			'ship_types' => $this->getShipTypes(),
			'turns' => array_map(fn (TurnResult $turn) => $turn->toArray(), $turns),
			...$this->getOutcome($state, $turns),
		];
	}

	/**
	 * One turn for the live viewer, with the fleets that joined the battle during this turn.
	 *
	 * @param list<TurnResult> $turns every turn so far, only used once the battle is over
	 *
	 * @return array<string, mixed>
	 */
	public function presentTurn(BattleState $state, TurnResult $turn, array $turns): array
	{
		$joinedIdentifiers = array_map(
			fn (BattleEvent $event) => $event->payload['fleet'],
			array_filter($turn->events, fn (BattleEvent $event) => BattleEvent::FLEET_JOINED === $event->type),
		);
		$joinedFleets = array_values(array_filter($state->fleets, fn (BattleFleet $fleet) => in_array($fleet->identifier, $joinedIdentifiers, true)));

		return [
			'turn' => $turn->toArray(),
			'fleets' => array_map($this->presentFleet(...), $joinedFleets),
			'squadron_life' => $this->getSquadronLife($joinedFleets),
			...$this->getOutcome($state, $turns),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function presentFleet(BattleFleet $fleet): array
	{
		return [
			'id' => $fleet->identifier,
			'name' => $fleet->name,
			'side' => $fleet->side,
			'faction' => $fleet->faction,
			'faction_name' => 0 === $fleet->faction ? 'Sans faction' : $this->translator->trans(sprintf('factions.%d.name.official', $fleet->faction)),
			'officer_level' => $fleet->officerLevel,
			'arrival_turn' => $fleet->arrivalTurn,
			'traits' => array_map(fn (OfficerTrait $trait) => [
				'trait' => $trait->value,
				'name' => $this->translator->trans(sprintf('combat_simulator.traits.%s.name', $trait->value)),
				'description' => $this->translator->trans(sprintf('combat_simulator.traits.%s.description', $trait->value)),
			], $fleet->traits),
		];
	}

	/**
	 * @param list<TurnResult> $turns
	 *
	 * @return array{result?: array<string, mixed>, report?: array<string, mixed>}
	 */
	private function getOutcome(BattleState $state, array $turns): array
	{
		if (!$state->isOver) {
			return [];
		}

		$experience = ($this->computeBattleExperience)($state);

		return [
			'result' => [
				'winning_side' => $state->winningSide,
				'turns' => $state->turn,
				'experience' => $experience,
			],
			'report' => ($this->buildBattleReport)($state, $turns, $experience)->toArray(),
		];
	}

	/**
	 * @param list<BattleFleet> $fleets
	 *
	 * @return array<string, int>
	 */
	private function getSquadronLife(array $fleets): array
	{
		$life = [];

		foreach ($fleets as $fleet) {
			foreach ($fleet->squadrons as $squadron) {
				$life[$squadron->identifier] = $squadron->maxShipLife;
			}
		}

		return $life;
	}

	/**
	 * @return array<string, array{name: string, role: string, class: int, pev: int}>
	 */
	private function getShipTypes(): array
	{
		$shipTypes = [];

		foreach ($this->battleShipTypeRegistry->all() as $identifier => $type) {
			$shipTypes[$identifier] = [
				'name' => ($this->getBattleShipTypeName)($type),
				'role' => $type->role->value,
				'class' => $type->class->value,
				'pev' => $type->pev,
			];
		}

		return $shipTypes;
	}
}
