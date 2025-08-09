<?php

namespace App\Modules\Ares\Application\Handler;

use App\Modules\Ares\Application\Handler\Battle\ShipStatsHandler;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Model\ShipStat;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Ares\Domain\Service\GetSquadronPev;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Ship;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonus;
use Symfony\Component\Uid\Uuid;

readonly class CommanderArmyHandler
{
	public function __construct(
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		private GetSquadronPev $getSquadronPev,
		private ShipStatsHandler $shipStatsHandler,
		private PlayerBonusManager $playerBonusManager,
	) {
	}

	public function setArmy(Commander $commander): void
	{
		if (!$commander->hasArmySetted) {
			$playerBonus = null;

			if ($commander->player) {
				$playerBonus = $this->playerBonusManager->getBonusByPlayer($commander->player);
			}

			for ($i = 0; $i < $commander->level and $i < 25; ++$i) {
				$commander->army[$i] = (null !== ($squadron = $commander->findSquadron($i)))
					? $squadron
					: new Squadron(
						id: Uuid::v4(),
						commander: $commander,
						createdAt: new \DateTimeImmutable(),
						updatedAt: new \DateTimeImmutable(),
						lineCoord: Commander::$LINECOORD[$i],
						position: $i,
					);
				$this->initializeShips($commander->army[$i], $playerBonus);
				$commander->squadronsIds[] = $i;
			}
			$commander->hasArmySetted = true;
		}
	}

	public function getPev(Commander $commander): int
	{
		$this->setArmy($commander);
		$pev = 0;
		foreach ($commander->army as $squadron) {
			$pev += ($this->getSquadronPev)($squadron);
		}

		return $pev;
	}

	public function getPevToLoot(Commander $commander): int
	{
		$pev = 0;
		foreach ($commander->armyAtEnd as $squadron) {
			foreach (ShipCategory::cases() as $shipCategory) {
				$pev += $squadron[$shipCategory->value] * ($this->getShipCategoriesConfiguration)($shipCategory, 'pev');
			}
		}

		if (0 !== $pev) {
			return $pev;
		} else {
			return $this->getPev($commander);
		}
	}

	public function setArmyAtEnd(Commander $commander): void
	{
		$this->setArmy($commander);

		foreach ($commander->army as $key => $squadron) {
			$commander->armyAtEnd[$key] = $squadron->getShips();
		}
	}

	public function initializeShips(Squadron $squadron, ?PlayerBonus $playerBonus = null): void
	{
		if ($squadron->areShipsInitialized) {
			return;
		}

		foreach ($squadron->getShips() as $shipNumber => $quantity) {
			for ($i = 0; $i < $quantity; ++$i) {
				$squadron->ships[] = new Ship(
					id: $i,
					shipNumber: $shipNumber,
					life: $this->shipStatsHandler->getStatsByShipNumber($shipNumber, ShipStat::Life, $playerBonus),
					squadron: $squadron,
				);
			}
		}

		$squadron->areShipsInitialized = true;
	}
}
