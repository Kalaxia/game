<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle;

interface FactionHostilityInterface
{
	/**
	 * Whether fleets of these two factions fight each other when they meet in the same orbit.
	 */
	public function areEnemies(int $faction, int $otherFaction): bool;
}
