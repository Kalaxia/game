<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Battle;

use App\Modules\Ares\Domain\Model\Battle\ShipClass;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonusId;

/**
 * Description of an officer's fleet for CreateBattle and JoinBattle: its formation as composed
 * by the player (D9), the officer's level and traits, and the player's combat bonuses
 * (technologies, faction and laws).
 *
 * The squadrons are listed in a stable order (getSquadrons), so that the battle squadron
 * "{fleet}-{index}" can be matched back to the squadron of the fleet.
 */
readonly class DescribeCommanderFleet
{
	private const array BONUSES = [
		ShipClass::Fighter->value => [PlayerBonusId::FIGHTER_ATTACK, PlayerBonusId::FIGHTER_DEFENSE, PlayerBonusId::FIGHTER_SPEED],
		ShipClass::Corvette->value => [PlayerBonusId::CORVETTE_ATTACK, PlayerBonusId::CORVETTE_DEFENSE, PlayerBonusId::CORVETTE_SPEED],
		ShipClass::Frigate->value => [PlayerBonusId::FRIGATE_ATTACK, PlayerBonusId::FRIGATE_DEFENSE, PlayerBonusId::FRIGATE_SPEED],
		ShipClass::Destroyer->value => [PlayerBonusId::DESTROYER_ATTACK, PlayerBonusId::DESTROYER_DEFENSE, PlayerBonusId::DESTROYER_SPEED],
	];

	public function __construct(
		private BattleShipTypeRegistry $battleShipTypeRegistry,
		private PlayerBonusManager $playerBonusManager,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __invoke(Commander $commander, int $side): array
	{
		return [
			'identifier' => self::getIdentifier($commander),
			'name' => $commander->name,
			'side' => $side,
			'faction' => $commander->player->faction->identifier,
			'officer_level' => max(1, $commander->level),
			'traits' => $commander->traits,
			'formation' => array_map(fn (Squadron $squadron) => [
				'type' => $this->battleShipTypeRegistry->getByCategory($squadron->shipCategory)->identifier,
				'ships' => $squadron->ships,
				'q' => $squadron->q,
				'r' => $squadron->r,
			], self::getSquadrons($commander)),
			'faction_bonuses' => false,
			'bonuses' => $this->getBonuses($commander),
		];
	}

	public static function getIdentifier(Commander $commander): string
	{
		return $commander->id->toRfc4122();
	}

	/**
	 * Squadrons with ships, in the order of the formation given to the battle.
	 *
	 * @return list<Squadron>
	 */
	public static function getSquadrons(Commander $commander): array
	{
		$squadrons = array_values(array_filter($commander->squadrons->toArray(), fn (Squadron $squadron) => $squadron->ships > 0));
		usort($squadrons, fn (Squadron $a, Squadron $b) => [$a->q, $a->r] <=> [$b->q, $b->r]);

		return $squadrons;
	}

	/**
	 * @return array<int, array{attack: int, defense: int, speed: int}>
	 */
	private function getBonuses(Commander $commander): array
	{
		$playerBonus = $this->playerBonusManager->getBonusByPlayer($commander->player);
		$bonuses = [];

		foreach (self::BONUSES as $class => [$attack, $defense, $speed]) {
			$bonuses[$class] = [
				'attack' => intval(round($playerBonus->bonuses->get($attack) ?? 0)),
				'defense' => intval(round($playerBonus->bonuses->get($defense) ?? 0)),
				'speed' => intval(round($playerBonus->bonuses->get($speed) ?? 0)),
			];
		}

		return $bonuses;
	}
}
