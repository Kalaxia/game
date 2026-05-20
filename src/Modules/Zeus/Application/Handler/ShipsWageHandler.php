<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Application\Handler;

use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Service\CalculateFleetCost;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Zeus\Domain\Event\UnmaintainedHangarShipsEvent;
use App\Modules\Zeus\Domain\Event\UnpaidFleetEvent;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerFinancialReport;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class ShipsWageHandler
{
	public function __construct(
		private CalculateFleetCost $calculateFleetCost,
		private CommanderArmyHandler $commanderArmyHandler,
		private CommanderRepositoryInterface $commanderRepository,
		private EventDispatcherInterface $eventDispatcher,
		private TransactionRepositoryInterface $transactionRepository,
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		#[Autowire('%game.ship_cost_reduction%')]
		private float $shipCostReduction,
	) {
	}

	/**
	 * @param list<Commander> $commanders
	 * @param list<Planet>    $playerBases
	 */
	public function payWages(
		PlayerFinancialReport $playerFinancialReport,
		array $commanders,
		array $playerBases,
		Player $rebelPlayer,
	): void {
		$this->payForShipsInSale($playerFinancialReport);
		$this->payForFleets($playerFinancialReport, $commanders, $rebelPlayer);
		$this->payForShipsInHanger($playerFinancialReport, $playerBases);
	}

	private function payForShipsInSale(PlayerFinancialReport $playerFinancialReport): void
	{
		$player = $playerFinancialReport->player;
		$transactions = $this->transactionRepository->getPlayerPropositions($player, Transaction::TYP_SHIP);
		// payer l'entretien des vaisseaux
		// vaisseaux en vente
		$transactionTotalCost = 0;
		$nbTransactions = count($transactions);
		for ($i = ($nbTransactions - 1); $i >= 0; --$i) {
			$transaction = $transactions[$i];
			$transactionTotalCost += ($this->getShipCategoriesConfiguration)($transaction->identifier, 'cost') * $this->shipCostReduction * $transaction->quantity;
		}
		$playerFinancialReport->shipsCost += $transactionTotalCost;
		// if (!$playerFinancialReport->canAfford($transactionTotalCost)) {
		// $newCredit = 0;
		// }
	}

	/**
	 * @param list<Commander> $commanders
	 */
	private function payForFleets(PlayerFinancialReport $playerFinancialReport, array $commanders, Player $rebelPlayer): void
	{
		foreach ($commanders as $commander) {
			$this->commanderArmyHandler->setArmy($commander);

			$ships = $commander->getNbrShipByType();

			$cost = ($this->calculateFleetCost)($ships);

			if ($playerFinancialReport->canAfford($cost)) {
				$playerFinancialReport->shipsCost += $cost;
				continue;
			}
			// TODO investigate this WTF statement. Deserters could be rebel commanders (with faction quests to eliminate them).
			// on vend le commandant car on n'arrive pas à payer la flotte (trash hein)
			$commander->statement = Commander::ONSALE;
			$commander->player = $rebelPlayer;

			$this->commanderRepository->save($commander);

			$this->eventDispatcher->dispatch(new UnpaidFleetEvent($playerFinancialReport, $commander));
		}
	}

	/**
	 * @param list<Planet> $playerBases
	 */
	private function payForShipsInHanger(PlayerFinancialReport $playerFinancialReport, array $playerBases): void
	{
		foreach ($playerBases as $base) {
			$shipsStorage = $base->getShipStorage();
			$cost = ($this->calculateFleetCost)($shipsStorage, false);

			if ($playerFinancialReport->canAfford($cost)) {
				$playerFinancialReport->shipsCost += $cost;

				continue;
			}
			// n'arrive pas à tous les payer !
			$shipCategoriesCount = count(ShipCategory::cases());
			for ($shipIdentifier = $shipCategoriesCount - 1; $shipIdentifier >= 0; --$shipIdentifier) {
				if (0 === $shipsStorage[$shipIdentifier]) {
					continue;
				}
				$unitCost = ($this->getShipCategoriesConfiguration)($shipIdentifier, 'cost');

				$possibleMaintenable = intval(floor($playerFinancialReport->getNewWallet() / $unitCost));
				if ($possibleMaintenable > $shipsStorage[$shipIdentifier]) {
					$possibleMaintenable = $shipsStorage[$shipIdentifier];
				}
				$playerFinancialReport->shipsCost += $possibleMaintenable * $unitCost;

				$toKill = $shipsStorage[$shipIdentifier] - $possibleMaintenable;
				if (0 === $toKill) {
					continue;
				}
				$base->removeShips($shipIdentifier, $toKill);

				$this->eventDispatcher->dispatch(new UnmaintainedHangarShipsEvent(
					$playerFinancialReport,
					$base,
					$shipIdentifier,
					$toKill,
				));
			}
		}
	}
}
