<?php

namespace App\Modules\Ares\Manager;

use App\Modules\Ares\Application\Handler\Battle\SquadronFightHandler;
use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Application\Handler\CommanderExperienceHandler;
use App\Modules\Ares\Application\Handler\FightImportanceHandler;
use App\Modules\Ares\Domain\Repository\SquadronRepositoryInterface;
use App\Modules\Ares\Domain\Service\GetSquadronPev;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\LiveReport;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonus;
use Psr\Log\LoggerInterface;

// TODO Transform static properties into constants to set the class as readonly
class FightManager
{
	public function __construct(
		private readonly FightImportanceHandler $fightImportanceHandler,
		private readonly CommanderArmyHandler $commanderArmyHandler,
		private readonly CommanderExperienceHandler $commanderExperienceHandler,
		private readonly GetSquadronPev $getSquadronPev,
		private readonly SquadronFightHandler $squadronFightHandler,
		private readonly SquadronRepositoryInterface $squadronRepository,
		private readonly PlayerBonusManager $playerBonusManager,
		private readonly LoggerInterface $logger,
	) {
		self::$currentLine = 3;
	}

	// ATTRIBUT STATIC DE LIGNE COURANTE

	private static $currentLine = 0;

	// GETTER

	public static function getCurrentLine(): int
	{
		return self::$currentLine;
	}

	/**
	 * DEMARE LE COMBAT ENTRE DEUX COMMANDANT
	 *		COMPTE L'ARMEE D
	 *		si 0 vaisseaux
	 *			A gagne
	 *		SINON COMBAT
	 *		COMPTE L'ARMEE A
	 *		si 0 vaisseaux
	 *			D gagne
	 *		SINON COMBAT.
	 */
	public function startFight(Commander $attacker, Commander $defender): void
	{
		$this->commanderArmyHandler->setArmy($attacker);
		$attacker->isAttacker = true;
		$this->commanderArmyHandler->setArmy($defender);
		$defender->isAttacker = false;

		LiveReport::$rPlayerAttacker = $attacker->player;
		LiveReport::$rPlayerDefender = $defender->player;

		LiveReport::$attackerCommander = $attacker;
		LiveReport::$defenderCommander = $defender;
		LiveReport::$levelA = $attacker->level;
		LiveReport::$levelD = $defender->level;
		LiveReport::$experienceA = $attacker->experience;
		LiveReport::$experienceD = $defender->experience;
		LiveReport::$palmaresA = $attacker->palmares;
		LiveReport::$palmaresD = $defender->palmares;
		LiveReport::$attackerImportance = $this->fightImportanceHandler->calculateImportance($attacker, $defender);
		LiveReport::$defenderImportance = $this->fightImportanceHandler->calculateImportance($defender, $attacker);

		$this->logger->debug('Battle importance is {attacker_importance} for commander {attacker_name}, {defender_importance} for commander {defender_name}', [
			'attacker_importance' => LiveReport::$attackerImportance,
			'attacker_name' => $attacker->name,
			'defender_importance' => LiveReport::$defenderImportance,
			'defender_name' => $defender->name,
		]);

		$this->storeSquadronsInBegin($attacker);
		$this->storeSquadronsInBegin($defender);

		$this->processRounds($attacker, $defender);

		$this->logger->debug('Attacker has {attackerShipsCount} remaining ships, defender has {defenderShipsCount}', [
			'attackerShipsCount' => array_sum($attacker->getNbrShipByType()),
			'defenderShipsCount' => array_sum($defender->getNbrShipByType()),
		]);

		$this->updateCommander($attacker);
		$this->updateCommander($defender);
	}

	private function processRounds(Commander $attacker, Commander $defender): void
	{
		$attackerBonus = null !== $attacker->player ? $this->playerBonusManager->getBonusByPlayer($attacker->player) : null;
		$defenderBonus = null !== $defender->player ? $this->playerBonusManager->getBonusByPlayer($defender->player) : null;

		while (1000 > LiveReport::$round) {
			$this->logger->debug('Beginning round {roundNumber}', [
				'roundNumber' => LiveReport::$round,
			]);

			if (true === $this->processPartyRound($defender, $attacker, $defenderBonus, $attackerBonus)) {
				break;
			}

			if (true === $this->processPartyRound($attacker, $defender, $attackerBonus, $defenderBonus)) {
				break;
			}

			++LiveReport::$round;
			++self::$currentLine;
		}

		$this->storeRemainingSquadronsAtEnd($attacker);
		$this->storeRemainingSquadronsAtEnd($defender);
	}

	private function processPartyRound(
		Commander $commander,
		Commander $opponent,
		PlayerBonus|null $playerBonus = null,
		PlayerBonus|null $opponentBonus = null,
	): bool {
		$this->engage($commander, $opponent, $playerBonus, $opponentBonus);
		++LiveReport::$halfRound;

		$commanderShipsCount = 0;

		foreach ($commander->army as $squadron) {
			$commanderShipsCount += array_sum($squadron->getShips());
		}
		if (0 == $commanderShipsCount) {
			$this->processDeath($commander, $opponent);

			return true;
		}

		return false;
	}

	private function processDeath(Commander $commander, Commander $opponent): void
	{
		$this->logger->debug('Commander {name} died', [
			'name' => $commander->name,
		]);

		$this->resultOfFight($opponent, true, $commander);
		$this->resultOfFight($commander, false, $opponent);
		$commander->statement = Commander::DEAD;
		$commander->diedAt = new \DateTimeImmutable();
		LiveReport::$rPlayerWinner = $opponent->player;

		if (null !== $commander->player) {
			++$commander->player->defeat;
		}

		if (null !== $opponent->player) {
			++$opponent->player->victory;
		}
	}

	private function storeSquadronsInBegin(Commander $commander): void
	{
		foreach ($commander->army as $key => $squadron) {
			LiveReport::$squadrons[] = [
				0,
				$key,
				0,
				0,
				$commander,
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
		}
	}

	private function storeRemainingSquadronsAtEnd(Commander $commander): void
	{
		foreach ($commander->army as $key => $squadron) {
			LiveReport::$squadrons[] = [
				0,
				$key,
				0,
				-1,
				$commander,
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
		}
	}

	private function resultOfFight(Commander $commander, bool $isWinner, Commander $enemyCommander): void
	{
		if ($commander->isVirtual()) {
			return;
		}

		/* TODO VERIFY * */
		$this->commanderExperienceHandler->setEarnedExperience($commander, $enemyCommander);
		$commander->earnedExperience = intval(round($commander->earnedExperience));
		$commander->winner = $isWinner;
		$this->commanderArmyHandler->setArmyAtEnd($commander);

		$this->commanderExperienceHandler->upExperience($commander, $commander->earnedExperience);

		if ($isWinner) {
			LiveReport::$expCom = $commander->earnedExperience;

			++$commander->palmares;
		}
	}

	// ENGAGE UN COMBAT ENTRE CHAQUE SQUADRON CONTRE UN COMMANDANT
	private function engage(
		Commander $commander,
		Commander $enemyCommander,
		PlayerBonus|null $playerBonus = null,
		PlayerBonus|null $enemyBonus = null,
	): void {
		foreach ($commander->army as $squadron) {
			$squadron->targetId = null;
		}
		foreach ($commander->army as $squadron) {
			$squadronPev = ($this->getSquadronPev)($squadron);
			// TODO move to spec (same as SquadronManager::chooseEnemy)
			// The current line is just the rule to ensure that all the lines don't fire at the starting round
			if (0 < $squadronPev && $squadron->lineCoord * 3 <= FightManager::getCurrentLine()) {
				$this->squadronFightHandler->engage($squadron, $enemyCommander, $playerBonus, $enemyBonus);
			} elseif (0 === $squadronPev) {
				$this->logger->debug('Squadron {squadronPosition} of commander {commanderName} has no more ships to fight', [
					'squadronPosition' => $squadron->position,
					'commanderName' => $squadron->commander->name,
				]);
			} else {
				$this->logger->debug('Squadron {squadronPosition} from {squadronLine} line of commander {commanderName} does not fight in this round. Current line is {currentLine}', [
					'squadronPosition' => $squadron->position,
					'squadronLine' => $squadron->lineCoord,
					'commanderName' => $squadron->commander->name,
					'currentLine' => FightManager::getCurrentLine(),
				]);
			}
		}
	}

	private function updateCommander(Commander $commander): void
	{
		if ($commander->isVirtual()) {
			return;
		}

		foreach ($commander->squadrons as $squadron) {
			if (0 === $squadron->getShipsCount()) {
				$this->squadronRepository->remove($squadron);

				continue;
			}
			$this->squadronRepository->save($squadron);
		}
	}
}
