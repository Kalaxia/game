<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Handler\Battle;

use App\Modules\Ares\Domain\Model\ShipStat;
use App\Modules\Ares\Model\Ship;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Zeus\Model\PlayerBonus;
use Psr\Log\LoggerInterface;

readonly class ShipFightHandler
{
	public function __construct(
		private ShipStatsHandler $shipStatsHandler,
		private LoggerInterface $logger,
	) {

	}

	public function engage(Ship $ship, Squadron $enemySquadron, PlayerBonus|null $playerBonus = null): void
	{
		$attacksCount = count($this->shipStatsHandler->getStats($ship, ShipStat::Attack, $playerBonus));

		for ($attackNumber = 0; $attackNumber < $attacksCount; ++$attackNumber) {
			if (0 === $enemySquadron->getShipsCount()) {
				break;
			}
			$keyOfEnemyShip = $this->chooseEnemy($enemySquadron);
			if (!$this->doesDodge($enemySquadron->ships[$keyOfEnemyShip], $playerBonus)) {
				$this->attack($ship, $keyOfEnemyShip, $attackNumber, $enemySquadron, $playerBonus);
			} else {
				$this->logger->debug('{shipType} {shipId} of commander {commanderName} has dodged {attackerType} {attackerId} attack number {attackNumber}', [
					'shipType' => $this->shipStatsHandler->getStats($enemySquadron->ships[$keyOfEnemyShip], ShipStat::Name, $playerBonus),
					'shipId' => $keyOfEnemyShip,
					'commanderName' => $enemySquadron->commander->name,
					'attackerType' => $this->shipStatsHandler->getStats($ship, ShipStat::Name, $playerBonus),
					'attackerId' => $ship->id,
					'attackNumber' => $attackNumber,
				]);
			}
		}
	}

	private function chooseEnemy(Squadron $enemySquadron): int
	{
		return random_int(0, $enemySquadron->getShipsCount() - 1);
	}

	private function attack(Ship $ship, int $key, int $attackNumber, Squadron $enemySquadron, PlayerBonus|null $playerBonus = null): void
	{
		$targetShip = $enemySquadron->ships[$key];

		$attackDamage = $this->shipStatsHandler->getStats($ship, ShipStat::Attack, $playerBonus)[$attackNumber];

		$damages = intval(ceil(
			log((
				$attackDamage
				/ $this->shipStatsHandler->getStats($targetShip, ShipStat::Defense, $playerBonus)
			) + 1)
			* 4
			* $attackDamage
		));

		$this->logger->debug('{shipType} {attackerId} of commander {commanderName} has inflicted {damage} damage points to {targetType} {targetId} with attack number {attackNumber}. Life before: {life}', [
			'shipType' => $this->shipStatsHandler->getStats($ship, ShipStat::Name, $playerBonus),
			'targetType' => $this->shipStatsHandler->getStats($targetShip, ShipStat::Name, $playerBonus),
			'commanderName' => $ship->squadron->commander->name,
			'targetId' => $key,
			'life' => $targetShip->life,
			'damage' => $damages,
			'attackerId' => $ship->id,
			'attackNumber' => $attackNumber,
		]);

		$this->receiveDamages($enemySquadron, $key, $damages, $playerBonus);
	}

	private function doesDodge(Ship $ship, PlayerBonus|null $playerBonus = null): bool
	{
		$avoidance = random_int(0, intval(round($this->shipStatsHandler->getStats($ship, ShipStat::Speed, $playerBonus))));

		return $avoidance > 80;
	}

	private function receiveDamages(Squadron $squadron, int $key, int $damages, PlayerBonus|null $playerBonus = null): void
	{
		$ship = $squadron->ships[$key];

		$ship->life -= $damages;

		if ($ship->life <= 0) {
			$this->logger->debug('{targetType} {targetId} of commander {commanderName} has been destroyed', [
				'targetType' => $this->shipStatsHandler->getStats($ship, ShipStat::Name, $playerBonus),
				'targetId' => $key,
				'commanderName' => $ship->squadron->commander->name,
			]);

			$ship->life = 0;
			$squadron->destructShip($key);
		}
	}
}
