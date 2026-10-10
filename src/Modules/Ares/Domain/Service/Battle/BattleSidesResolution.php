<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle;

final readonly class BattleSidesResolution
{
	/**
	 * @param list<list<int>> $sides              faction identifiers of each side, in order of involvement
	 * @param list<int>       $uninvolvedFactions factions present in orbit that do not take part in the battle
	 */
	public function __construct(
		public array $sides,
		public array $uninvolvedFactions,
	) {
	}

	public function hasBattle(): bool
	{
		return count($this->sides) > 1;
	}
}
