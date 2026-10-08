<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Modules\Ares\Domain\Model\Battle\Grid\HexDirection;

/**
 * A fleet taking part in a battle: one officer, its squadrons and its area on the battlefield.
 */
class BattleFleet
{
	/** Battlefield cluster the fleet stands on, null until deployed */
	public ?Hex $cluster = null;
	/** Direction the front line of the formation faces */
	public HexDirection $orientation = HexDirection::East;
	/** Whether the formation has been turned toward an enemy yet */
	public bool $hasFacedEnemy = false;
	/** Whether the destruction of the fleet has already been reported in the event log */
	public bool $isDestructionReported = false;
	public int $damageDealt = 0;
	public int $damageTaken = 0;
	/** PEV of the enemy ships destroyed by the fleet */
	public int $pevDestroyed = 0;
	/** Disorganisation due to the fleet size, between 0 and 1 (D10) */
	public float $sizeMalus = 0.0;
	/** Between 0 and 100 (D12) */
	public float $morale = 100.0;
	public FleetStatus $status = FleetStatus::Engaged;
	public ?int $fleeingSinceTurn = null;

	/**
	 * @param list<FormationSlot>  $formation squadrons to deploy, positioned relative to the cluster center, front facing east
	 * @param list<BattleSquadron> $squadrons squadrons on the battle grid once deployed
	 * @param list<OfficerTrait>   $traits    traits of the officer (Q13)
	 */
	public function __construct(
		public readonly string $identifier,
		public readonly string $name,
		public readonly int $faction,
		public readonly int $side,
		public readonly int $officerLevel,
		public readonly array $formation,
		/** Turn at which the fleet arrives in the battle */
		public readonly int $arrivalTurn = 1,
		public array $squadrons = [],
		public readonly BattleBonuses $bonuses = new BattleBonuses(),
		public readonly array $traits = [],
	) {
		if (count($traits) > OfficerTrait::MAX_PER_OFFICER) {
			throw new \InvalidArgumentException(sprintf('An officer has at most %d traits', OfficerTrait::MAX_PER_OFFICER));
		}
	}

	public function hasTrait(OfficerTrait $trait): bool
	{
		return in_array($trait, $this->traits, true);
	}

	public function isDeployed(): bool
	{
		return null !== $this->cluster;
	}

	public function isDestroyed(): bool
	{
		return $this->isDeployed() && [] === $this->getActiveSquadrons();
	}

	public function hasFled(): bool
	{
		return FleetStatus::Fled === $this->status;
	}

	/**
	 * Only engaged fleets fire: withdrawn and fleeing ones do not (D12).
	 */
	public function canFire(): bool
	{
		return FleetStatus::Engaged === $this->status;
	}

	public function isPendingArrival(int $turn): bool
	{
		return !$this->isDeployed() && $this->arrivalTurn > $turn;
	}

	/**
	 * @return list<BattleSquadron>
	 */
	public function getActiveSquadrons(): array
	{
		return array_values(array_filter($this->squadrons, fn (BattleSquadron $squadron) => !$squadron->isDestroyed()));
	}

	public function getPev(): int
	{
		return array_sum(array_map(fn (BattleSquadron $squadron) => $squadron->getPev(), $this->squadrons));
	}

	public function getFormationPev(): int
	{
		return array_sum(array_map(fn (FormationSlot $slot) => $slot->ships * $slot->type->pev, $this->formation));
	}
}
