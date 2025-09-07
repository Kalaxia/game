<?php

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
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerFinancialReport;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class ShipsWageHandler
{
	public function __construct(
		private CalculateFleetCost $calculateFleetCost,
		private CommanderArmyHandler $commanderArmyHandler,
		private CommanderRepositoryInterface $commanderRepository,
		private NotificationRepositoryInterface $notificationRepository,
		private TransactionRepositoryInterface  $transactionRepository,
		private TranslatorInterface $translator,
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		#[Autowire('%game.ship_cost_reduction%')]
		private float $shipCostReduction,
	) {
	}

	/**
	 * @param list<Commander>   $commanders
	 * @param list<Planet> $playerBases
	 */
	public function payWages(
		PlayerFinancialReport $playerFinancialReport,
		array $commanders,
		array $playerBases,
		Player $rebelPlayer,
	): void {
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
		if ($playerFinancialReport->canAfford($transactionTotalCost)) {
			$playerFinancialReport->shipsCost += $transactionTotalCost;
			// } else {
			// TODO decide what to do when ships in transaction cannot be paid
			// $newCredit = 0;
		}
		// vaisseaux affectés
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

			$notification = NotificationBuilder::new()
				->setTitle('Flotte impayée')
				->setContent(NotificationBuilder::paragraph(
					'Vous n\'avez pas assez de crédits pour payer l\'entretien de la flotte de votre officier ',
					$commander->name,
					'Celui-ci a donc déserté ! ... avec la flotte, désolé.',
				))
				->for($player);
			$this->notificationRepository->save($notification);
			$this->commanderRepository->save($commander);
		}
		// vaisseaux sur la planète
		// TODO refactor this part for better carving
		foreach ($playerBases as $base) {
			$shipsStorage = $base->getShipStorage();
			$cost = ($this->calculateFleetCost)($shipsStorage, false);

			if ($playerFinancialReport->canAfford($cost)) {
				$playerFinancialReport->shipsCost += $cost;

				continue;
			}
			// n'arrive pas à tous les payer !
			$shipCategoriesCount = count(ShipCategory::cases());
			for ($j = $shipCategoriesCount - 1; $j >= 0; --$j) {
				if (0 === $shipsStorage[$j]) {
					continue;
				}
				$unitCost = ($this->getShipCategoriesConfiguration)($j, 'cost');

				$possibleMaintenable = floor($playerFinancialReport->getNewWallet() / $unitCost);
				if ($possibleMaintenable > $shipsStorage[$j]) {
					$possibleMaintenable = $shipsStorage[$j];
				}
				$playerFinancialReport->shipsCost += $possibleMaintenable * $unitCost;

				$toKill = $shipsStorage[$j] - $possibleMaintenable;
				if (0 === $toKill) {
					continue;
				}
				$base->removeShips($j, $toKill);

				$notification = NotificationBuilder::new()
					->setTitle('Entretien vaisseau impayé')
					->setContent(NotificationBuilder::paragraph(
						'Domaine',
						NotificationBuilder::divider(),
						'Vous n\'avez pas assez de crédits pour payer l\'entretien',
						(1 == $toKill)
							? sprintf(
								' d\'un(e) %s sur %s. Ce vaisseau part donc à la casse ! ',
								$this->translator->trans(sprintf('ship_categories.%s.name', $j)),
								$base->name,
							)
							: sprintf(
								'Vous n\'avez pas assez de crédits pour payer l\'entretien de %d %ss sur %s.
								Ces vaisseaux partent donc à la casse !',
								$toKill,
								$this->translator->trans(sprintf('ship_categories.%s.name', $j)),
								$base->name,
							)
					))
					->for($player);
				$this->notificationRepository->save($notification);
			}
		}
	}
}
