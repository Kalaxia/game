<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

/**
 * Percentage modifiers of a fleet's ship stats, by ship class.
 *
 * They come from the technologies, the faction and the laws of the fleet owner.
 * Unlike the legacy combat system, cruisers get the destroyer class bonuses.
 */
final readonly class BattleBonuses
{
	/**
	 * @param array<int, array{attack: int, defense: int, speed: int}> $percentages by ship class value
	 */
	public function __construct(
		private array $percentages = [],
	) {
	}

	public function add(ShipClass $class, int $attack = 0, int $defense = 0, int $speed = 0): self
	{
		$current = $this->percentages[$class->value] ?? ['attack' => 0, 'defense' => 0, 'speed' => 0];

		return new self([
			$class->value => [
				'attack' => $current['attack'] + $attack,
				'defense' => $current['defense'] + $defense,
				'speed' => $current['speed'] + $speed,
			],
		] + $this->percentages);
	}

	public function merge(self $other): self
	{
		$merged = $this;

		foreach ($other->percentages as $class => $percentages) {
			$merged = $merged->add(ShipClass::from($class), ...$percentages);
		}

		return $merged;
	}

	public function applyToAttack(ShipClass $class, int $attack): float
	{
		return $attack * (1 + $this->get($class, 'attack') / 100);
	}

	public function applyToDefense(ShipClass $class, int $defense): float
	{
		return $defense * (1 + $this->get($class, 'defense') / 100);
	}

	public function applyToSpeed(ShipClass $class, int $speed): float
	{
		return $speed * (1 + $this->get($class, 'speed') / 100);
	}

	/**
	 * @return array<int, array{attack: int, defense: int, speed: int}>
	 */
	public function toArray(): array
	{
		return $this->percentages;
	}

	private function get(ShipClass $class, string $stat): int
	{
		return $this->percentages[$class->value][$stat] ?? 0;
	}
}
