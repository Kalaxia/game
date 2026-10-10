<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;

/**
 * A squadron of identical ships on the battle grid.
 *
 * Shots always hit the leading ship, the most damaged one: damage beyond its remaining life is lost,
 * as in the legacy combat system.
 */
class BattleSquadron
{
	public int $leadShipLife;
	/** Life of each ship: the type's life multiplied by the battle durability factor */
	public readonly int $maxShipLife;
	/** Initiative accumulated over the rounds the squadron could fire but was not allowed to */
	public int $waitingInitiative = 0;

	public function __construct(
		public readonly string $identifier,
		public readonly BattleShipType $type,
		public int $ships,
		public Hex $cell,
		public ?BattleFleet $fleet = null,
		?int $maxShipLife = null,
	) {
		if ($ships < 1 || $ships > $type->squadronSize) {
			throw new \InvalidArgumentException(sprintf('A %s squadron holds 1 to %d ships, %d given', $type->identifier, $type->squadronSize, $ships));
		}

		$this->maxShipLife = $maxShipLife ?? $type->life;
		$this->leadShipLife = $this->maxShipLife;
	}

	public function isDestroyed(): bool
	{
		return 0 === $this->ships;
	}

	/**
	 * Initiative of the ship type, lowered by the fleet-size malus (D10) and changed by the officer traits (Q13).
	 */
	public function getInitiative(): int
	{
		if (null === $this->fleet) {
			return $this->type->initiative;
		}

		return (int) round($this->type->initiative * (1 - $this->fleet->sizeMalus) * OfficerTraitEffects::getInitiativeFactor($this->fleet));
	}

	public function getPev(): int
	{
		return $this->ships * $this->type->pev;
	}

	/**
	 * @return int number of ships destroyed
	 */
	public function receiveDamage(int $damage): int
	{
		if ($this->isDestroyed()) {
			return 0;
		}

		$this->leadShipLife -= $damage;

		if ($this->leadShipLife > 0) {
			return 0;
		}

		--$this->ships;
		$this->leadShipLife = $this->maxShipLife;

		return 1;
	}

	public function getFleet(): BattleFleet
	{
		return $this->fleet ?? throw new \LogicException(sprintf('Squadron %s has no fleet', $this->identifier));
	}
}
