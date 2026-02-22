<?php

namespace App\Modules\Ares\Application\Handler\Battle;

use App\Modules\Ares\Manager\FightManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\LiveReport;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Zeus\Model\PlayerBonus;
use Psr\Log\LoggerInterface;

readonly class SquadronFightHandler
{
	public function __construct(
		private LoggerInterface $logger,
		private ShipFightHandler $shipFightHandler,
	) {
	}

	public function engage(
		Squadron $squadron,
		Commander $enemyCommander,
		?PlayerBonus $attackerBonus = null,
		?PlayerBonus $enemyBonus = null,
	): void {
		$squadron->targetId = $this->chooseEnemy($squadron, $enemyCommander);
		if (null === $squadron->targetId) {
			$this->logger->debug('Squadron {squadronPosition} of commander {commanderName} has not found any enemy', [
				'squadronPosition' => $squadron->position,
				'commanderName' => $squadron->commander->name,
			]);

			return;
		}

		$enemySquadron = $enemyCommander->getSquadron($squadron->targetId);

		$this->logger->debug('Squadron {squadronPosition} of commander {commanderName} will fight Squadron {targetPosition}', [
			'squadronPosition' => $squadron->position,
			'targetPosition' => $enemySquadron->position,
			'commanderName' => $squadron->commander->name,
		]);

		++LiveReport::$littleRound;

		$this->fight($squadron, $enemySquadron, $attackerBonus);

		LiveReport::$squadrons[] = [
			0,
			$squadron->position,
			0,
			LiveReport::$littleRound,
			$squadron->commander,
			$squadron->getShipQuantity(0),
			$squadron->getShipQuantity(1),
			$squadron->getShipQuantity(2),
			$squadron->getShipQuantity(3),
			$squadron->getShipQuantity(4),
			$squadron->getShipQuantity(5),
			$squadron->getShipQuantity(6),
			$squadron->getShipQuantity(7),
			$squadron->getShipQuantity(8),
			$squadron->getShipQuantity(9),
			$squadron->getShipQuantity(10),
			$squadron->getShipQuantity(11),
		];
		LiveReport::$squadrons[] = [
			0,
			$enemySquadron->position,
			0,
			LiveReport::$littleRound,
			$enemyCommander,
			$enemySquadron->getShipQuantity(0),
			$enemySquadron->getShipQuantity(1),
			$enemySquadron->getShipQuantity(2),
			$enemySquadron->getShipQuantity(3),
			$enemySquadron->getShipQuantity(4),
			$enemySquadron->getShipQuantity(5),
			$enemySquadron->getShipQuantity(6),
			$enemySquadron->getShipQuantity(7),
			$enemySquadron->getShipQuantity(8),
			$enemySquadron->getShipQuantity(9),
			$enemySquadron->getShipQuantity(10),
			$enemySquadron->getShipQuantity(11),
		];

		$enemySquadron->targetId = $squadron->position;

		$this->fight($enemySquadron, $squadron, $enemyBonus);
	}

	private function chooseEnemy(Squadron $squadron, Commander $enemyCommander): ?int
	{
		$nbrShipsInLine = 0;
		foreach ($enemyCommander->army as $enemySquadron) {
			if ($enemySquadron->lineCoord * 3 <= FightManager::getCurrentLine()) {
				$nbrShipsInLine += $enemySquadron->getShipsCount();
			}
		}
		if (0 == $nbrShipsInLine) {
			return null;
		} elseif (null != $squadron->targetId and $enemyCommander->getSquadron($squadron->targetId)->getShipsCount() > 0) {
			return $squadron->targetId;
		}
		/** @var list<Squadron> $squadrons */
		$squadrons = $enemyCommander->squadrons->toArray();
		shuffle($squadrons);

		foreach ($squadrons as $squadron) {
			if ($squadron->lineCoord * 3 <= FightManager::getCurrentLine() && $squadron->getShipsCount() > 0) {
				return $squadron->position;
			}
		}

		return null;
	}

	private function fight(Squadron $squadron, Squadron $enemySquadron, ?PlayerBonus $playerBonus = null): void
	{
		foreach ($squadron->ships as $ship) {
			if (0 === $enemySquadron->getShipsCount()) {
				break;
			}
			$this->shipFightHandler->engage($ship, $enemySquadron, $playerBonus);
		}
	}
}
