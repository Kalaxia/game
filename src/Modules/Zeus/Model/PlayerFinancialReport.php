<?php

namespace App\Modules\Zeus\Model;

use Symfony\Component\Uid\Uuid;

class PlayerFinancialReport
{
	public function __construct(
		public readonly Uuid $id,
		public readonly Player $player,
		public readonly \DateTimeImmutable $createdAt,
		public readonly int $initialWallet,
		// INCOME
		public int $populationTaxes = 0,
		public int $resourcesSales = 0,
		public int $shipsSales = 0,
		public int $commandersSales = 0,
		public int $recycledCredits = 0,
		public int $receivedPlayersCreditTransactions = 0,
		public int $receivedFactionsCreditTransactions = 0,
		// EXPENSES
		public int $factionTaxes = 0,
		public int $antiSpyInvestments = 0,
		public int $universityInvestments = 0,
		public int $schoolInvestments = 0,
		public int $technologiesInvestments = 0,
		public int $conquestInvestments = 0,
		public int $commercialRoutesConstructions = 0,
		public int $commandersWages = 0,
		public int $shipsCost = 0,
		public int $resourcesPurchases = 0,
		public int $shipsPurchases = 0,
		public int $commandersPurchases = 0,
		public int $sentPlayersCreditTransactions = 0,
		public int $sentFactionsCreditTransactions = 0,
	) {
	}

	public function getTotalIncome(): int
	{
		return $this->populationTaxes
			+ $this->resourcesSales
			+ $this->shipsSales
			+ $this->commandersSales
			+ $this->receivedPlayersCreditTransactions
			+ $this->receivedFactionsCreditTransactions
			+ $this->recycledCredits;
	}

	public function getTotalLosses(): int
	{
		return $this->factionTaxes
			+ $this->antiSpyInvestments
			+ $this->universityInvestments
			+ $this->commandersWages
			+ $this->shipsCost
			+ $this->shipsPurchases
			+ $this->resourcesPurchases
			+ $this->commandersPurchases
			+ $this->technologiesInvestments
			+ $this->conquestInvestments
			+ $this->commercialRoutesConstructions
			+ $this->sentFactionsCreditTransactions
			+ $this->sentPlayersCreditTransactions;
	}

	public function getDiff(): int
	{
		return $this->getTotalIncome() - $this->getTotalLosses();
	}

	public function getNewWallet(): int
	{
		return $this->initialWallet + $this->getDiff();
	}

	public function canAfford(int $amount): bool
	{
		return $this->getNewWallet() >= $amount;
	}
}
