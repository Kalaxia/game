<?php

namespace App\Modules\Athena\Manager;

use App\Modules\Athena\Domain\Repository\CommercialTaxRepositoryInterface;
use App\Modules\Athena\Domain\Service\Transaction\CalculateRate;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Travel\Domain\Service\GetTravelDuration;
use App\Shared\Application\Handler\DurationHandler;

readonly class TransactionManager
{
	public function __construct(
		private CalculateRate $calculateRate,
		private DurationHandler $durationHandler,
		private GetTravelDuration $getTravelDuration,
		private CommercialTaxRepositoryInterface $commercialTaxRepository,
	) {
	}

	/**
	 * @return array{
	 *  export_price: int,
	 * 	export_tax: int,
	 *  export_faction: Color,
	 *  import_price: int,
	 * 	import_tax: int,
	 *  import_faction: Color,
	 *  total_price: int,
	 *  time: float,
	 *  rate: float|null,
	 * }
	 */
	public function getTransactionData(Transaction $transaction, Planet $ob, ?float $currentRate = null): array
	{
		//	$rv = '1:' . Format::numberFormat(Game::calculateRate($transaction->type, $transaction->quantity, $transaction->identifier, $transaction->price), 3);
		if (null !== $currentRate) {
			// TODO check if round's precision argument is not missing there
			$rate = round(($this->calculateRate)($transaction) / $currentRate * 100);
		}
		$transactionSystem = $transaction->base->system;
		$baseSystem = $ob->system;

		$departureDate = new \DateTimeImmutable();
		$arrivalDate = ($this->getTravelDuration)(
			origin: $transaction->base,
			destination: $ob,
			departureDate: $departureDate,
			travelType: TravelType::CommercialShipping,
			player: $transaction->player,
		);
		$time = $this->durationHandler->getDiff($departureDate, $arrivalDate);

		$transactionFaction = $transactionSystem->sector->faction;
		$baseFaction = $baseSystem->sector->faction;

		$transactionFactionTax = $this->commercialTaxRepository->getFactionsTax($transactionFaction, $baseFaction);
		$exportTax = $transactionFactionTax->exportTax;
		$exportFaction = $transactionFactionTax->faction;

		$baseFactionTax = $this->commercialTaxRepository->getFactionsTax($baseFaction, $transactionFaction);
		$importTax = $baseFactionTax->importTax;
		$importFaction = $baseFactionTax->faction;

		$exportPrice = PercentageApplier::toInt($transaction->price, $exportTax);
		$importPrice = PercentageApplier::toInt($transaction->price, $importTax);

		return [
			'export_price' => $exportPrice,
			'export_tax' => $exportTax,
			'export_faction' => $exportFaction,
			'import_price' => $importPrice,
			'import_tax' => $importTax,
			'import_faction' => $importFaction,
			'total_price' => $transaction->price + $exportPrice + $importPrice,
			'time' => $time,
			'rate' => $rate ?? null,
		];
	}
}
