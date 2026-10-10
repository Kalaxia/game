<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle;

/**
 * Splits the factions present in an orbit into battle sides.
 *
 * Rules (see docs/fleet-combat/plan.md, Q2, Q3 and D7):
 * - a faction hostile to some sides but not to others joins the first side it is not hostile to;
 * - a faction hostile to every side fights everyone, as a new side;
 * - a faction hostile to no side stays out of the battle. Uninvolved factions that are hostile
 *   to each other will fight in another battle, once this one is over.
 */
readonly class ResolveBattleSides
{
	public function __construct(
		private FactionHostilityInterface $factionHostility,
	) {
	}

	/**
	 * @param list<int>       $factions     faction identifiers, in order of arrival in the orbit
	 * @param list<list<int>> $currentSides sides of an ongoing battle, if any
	 */
	public function __invoke(array $factions, array $currentSides = []): BattleSidesResolution
	{
		$sides = $currentSides;
		$pendingFactions = array_values(array_unique($factions));

		// A faction staying out may get involved once one of its enemies joins, hence the loop until nothing changes
		do {
			$hasChanged = false;
			$stillPendingFactions = [];

			foreach ($pendingFactions as $faction) {
				$sideIndex = $this->findSide($sides, $faction);

				if (null === $sideIndex) {
					$stillPendingFactions[] = $faction;

					continue;
				}

				$sides[$sideIndex] ??= [];
				if (!in_array($faction, $sides[$sideIndex], true)) {
					$sides[$sideIndex][] = $faction;
				}
				$hasChanged = true;
			}

			$pendingFactions = $stillPendingFactions;
		} while ($hasChanged && [] !== $pendingFactions);

		return new BattleSidesResolution(array_values($sides), $pendingFactions);
	}

	/**
	 * @param list<list<int>> $sides
	 *
	 * @return int|null index of the side to join, a new index to open a new side, or null to stay out
	 */
	private function findSide(array $sides, int $faction): ?int
	{
		if ([] === $sides) {
			return 0;
		}

		$firstFriendlySideIndex = null;
		$isHostileToAnySide = false;

		foreach ($sides as $index => $side) {
			if (in_array($faction, $side, true)) {
				return $index;
			}

			if ($this->isHostileToSide($faction, $side)) {
				$isHostileToAnySide = true;
			} else {
				$firstFriendlySideIndex ??= $index;
			}
		}

		if (!$isHostileToAnySide) {
			return null;
		}

		return $firstFriendlySideIndex ?? count($sides);
	}

	/**
	 * @param list<int> $side
	 */
	private function isHostileToSide(int $faction, array $side): bool
	{
		foreach ($side as $sideFaction) {
			if ($this->factionHostility->areEnemies($faction, $sideFaction)) {
				return true;
			}
		}

		return false;
	}
}
