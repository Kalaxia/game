<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use App\Modules\Ares\Domain\Service\Battle\FactionHostilityInterface;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;

/**
 * Hostility based on the diplomatic relations of the factions.
 *
 * Relations can be one-sided, so a single ENEMY declaration is enough for the fleets to fight.
 * Rebels (NPC) are enemies of everyone.
 */
readonly class ColorFactionHostility implements FactionHostilityInterface
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
	) {
	}

	public function areEnemies(int $faction, int $otherFaction): bool
	{
		if ($faction === $otherFaction) {
			return false;
		}

		if (ColorResource::NO_FACTION === $faction || ColorResource::NO_FACTION === $otherFaction) {
			return true;
		}

		return $this->hasDeclaredEnemy($faction, $otherFaction) || $this->hasDeclaredEnemy($otherFaction, $faction);
	}

	private function hasDeclaredEnemy(int $faction, int $otherFaction): bool
	{
		$color = $this->colorRepository->getOneByIdentifier($faction)
			?? throw new \InvalidArgumentException(sprintf('Faction %d not found', $faction));

		return Color::ENEMY === ($color->relations[$otherFaction] ?? Color::NEUTRAL);
	}
}
