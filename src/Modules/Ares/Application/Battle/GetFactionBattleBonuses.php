<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleBonuses;
use App\Modules\Ares\Domain\Model\Battle\ShipClass;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Combat bonuses granted by a faction (config/game/factions.yaml).
 *
 * Same values as PlayerBonusManager::addFactionBonus, except that the small ships defense bonus
 * is applied once: the legacy code applies it twice (see docs/gameplay.md, known issues).
 */
readonly class GetFactionBattleBonuses
{
	/**
	 * @param array<int, array{bonus?: list<int>}> $factionsConfiguration
	 */
	public function __construct(
		#[Autowire('%app.factions%')]
		private array $factionsConfiguration,
	) {
	}

	public function __invoke(int $faction): BattleBonuses
	{
		$factionBonuses = $this->factionsConfiguration[$faction]['bonus'] ?? [];
		$bonuses = new BattleBonuses();

		if (in_array(ColorResource::DEFENSELITTLESHIPBONUS, $factionBonuses, true)) {
			foreach (ShipClass::cases() as $class) {
				$bonuses = $bonuses->add($class, defense: 5);
			}
		}

		if (in_array(ColorResource::SPEEDLITTLESHIPBONUS, $factionBonuses, true)) {
			$bonuses = $bonuses
				->add(ShipClass::Fighter, speed: 10)
				->add(ShipClass::Corvette, speed: 10);
		}

		if (in_array(ColorResource::DEFENSELITTLESHIPMALUS, $factionBonuses, true)) {
			$bonuses = $bonuses
				->add(ShipClass::Frigate, defense: -5)
				->add(ShipClass::Destroyer, defense: -5);
		}

		return $bonuses;
	}
}
