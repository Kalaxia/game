<?php

namespace App\Modules\Ares\Application\Handler\Battle;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Model\ShipStat;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Ares\Model\Ship;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;

readonly class ShipStatsHandler
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	public function getStats(Ship $ship, ShipStat $stat, ?PlayerBonus $playerBonus = null): mixed
	{
		return $this->getStatsByShipNumber($ship->shipNumber, $stat, $playerBonus);
	}

	public function getStatsByShipNumber(int $shipNumber, ShipStat $stat, ?PlayerBonus $playerBonus = null): mixed
	{
		if (in_array($stat, [ShipStat::Name])) {
			trigger_error('Calling ShipStatsHandler to retrieve a ship\'s name is deprecated, use the translator instead', E_USER_DEPRECATED);
		}

		$initialValue = ($this->getShipCategoriesConfiguration)($shipNumber, $stat->value);

		if (null !== $playerBonus && null !== ($bonusId = $this->getBonusIdForStat($shipNumber, $stat))) {
			if (ShipStat::Attack === $stat) {
				return array_map(
					fn (int $damage) => $damage + $this->bonusApplier->apply($damage, $bonusId, $playerBonus),
					$initialValue,
				);
			}

			return $initialValue + $this->bonusApplier->apply(
				$initialValue,
				$bonusId,
				$playerBonus,
			);
		}

		return $initialValue;
	}

	/**
	 * TODO think bout a way to be warned when a ship does not have an associated bonus.
	 */
	private function getBonusIdForStat(int $shipNumber, ShipStat $stat): ?int
	{
		$shipCategory = ShipCategory::tryFrom($shipNumber);

		return match ($stat) {
			ShipStat::Attack => match ($shipCategory) {
				ShipCategory::LightFighter, ShipCategory::Fighter, ShipCategory::HeavyFighter => PlayerBonusId::FIGHTER_ATTACK,
				ShipCategory::LightCorvette, ShipCategory::Corvette, ShipCategory::HeavyCorvette => PlayerBonusId::CORVETTE_ATTACK,
				ShipCategory::LightFrigate, ShipCategory::Frigate => PlayerBonusId::FRIGATE_ATTACK,
				ShipCategory::Destroyer, ShipCategory::HeavyDestroyer => PlayerBonusId::DESTROYER_ATTACK,
				default => null,
			},
			ShipStat::Defense => match ($shipCategory) {
				ShipCategory::LightFighter, ShipCategory::Fighter, ShipCategory::HeavyFighter => PlayerBonusId::FIGHTER_DEFENSE,
				ShipCategory::LightCorvette, ShipCategory::Corvette, ShipCategory::HeavyCorvette => PlayerBonusId::CORVETTE_DEFENSE,
				ShipCategory::LightFrigate, ShipCategory::Frigate => PlayerBonusId::FRIGATE_DEFENSE,
				ShipCategory::Destroyer, ShipCategory::HeavyDestroyer => PlayerBonusId::DESTROYER_DEFENSE,
				default => null,
			},
			ShipStat::Speed => match ($shipCategory) {
				ShipCategory::LightFighter, ShipCategory::Fighter, ShipCategory::HeavyFighter => PlayerBonusId::FIGHTER_SPEED,
				ShipCategory::LightCorvette, ShipCategory::Corvette, ShipCategory::HeavyCorvette => PlayerBonusId::CORVETTE_SPEED,
				ShipCategory::LightFrigate, ShipCategory::Frigate => PlayerBonusId::FRIGATE_SPEED,
				ShipCategory::Destroyer, ShipCategory::HeavyDestroyer => PlayerBonusId::DESTROYER_SPEED,
				default => null,
			},
			default => null,
		};
	}
}
