<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Support;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

final class BuildCommander
{
	public static function player(int $id, int $faction): Player
	{
		$player = new Player();
		$player->id = $id;
		$player->name = sprintf('player-%d', $id);
		$player->faction = new Color(id: Uuid::v4(), identifier: $faction);

		return $player;
	}

	/**
	 * @param list<array{ShipCategory, int, int, int}> $squadrons category, ships, q, r
	 */
	public static function build(?Player $player, ?Planet $base, array $squadrons = [], int $level = 3, string $name = 'officer'): Commander
	{
		$commander = new Commander(
			id: Uuid::v7(),
			name: $name,
			avatar: 'avatar',
			player: $player,
			base: $base,
			enlistedAt: new \DateTimeImmutable(),
			level: $level,
			statement: Commander::AFFECTED,
		);

		foreach ($squadrons as [$category, $ships, $q, $r]) {
			$commander->squadrons->add(new Squadron(Uuid::v7(), $commander, $category, $ships, $q, $r, new \DateTimeImmutable(), new \DateTimeImmutable()));
		}

		return $commander;
	}
}
