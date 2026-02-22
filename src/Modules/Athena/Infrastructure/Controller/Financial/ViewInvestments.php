<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Financial;

use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Service\CalculateFleetCost;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Handler\Tax\PopulationTaxHandler;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

class ViewInvestments extends AbstractController
{
	public function __construct(
		private readonly BonusApplierInterface $bonusApplier,
		private readonly CommanderArmyHandler $commanderArmyHandler,
		private readonly CommercialRouteRepositoryInterface $commercialRouteRepository,
		private readonly PopulationTaxHandler $populationTaxHandler,
		private readonly GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
		private readonly CalculateFleetCost $calculateFleetCost,
		#[Autowire('%game.ship_cost_reduction%')]
		private readonly float $shipCostReduction,
	) {
	}

	public function __invoke(
		Player $currentPlayer,
		CommanderRepositoryInterface $commanderRepository,
		PlanetRepositoryInterface $planetRepository,
		TransactionRepositoryInterface $transactionRepository,
	): Response {
		$taxCoeff = $this->getParameter('zeus.player.tax_coeff');

		$playerPlanets = $planetRepository->getPlayerPlanets($currentPlayer);

		$commanders = $commanderRepository->getPlayerCommanders(
			$currentPlayer,
			[Commander::AFFECTED, Commander::MOVING],
			['c.base' => 'ASC'],
		);

		$transactions = $transactionRepository->getPlayerPropositions($currentPlayer, Transaction::TYP_SHIP);

		$basesData = $this->getBasesData($playerPlanets, $taxCoeff);

		return $this->render('pages/athena/financial/investments.html.twig', [
			'commanders' => $commanders,
			'commanders_by_base' => array_reduce($commanders, function ($carry, Commander $commander) {
				$commanderBaseId = $commander->base->id->toRfc4122();
				if (!isset($carry[$commanderBaseId])) {
					$carry[$commanderBaseId] = [];
				}
				$this->commanderArmyHandler->setArmy($commander);
				$carry[$commanderBaseId][] = $commander;

				return $carry;
			}, []),
			'player_planets' => $playerPlanets,
			'tax_coeff' => $taxCoeff,
			'transactions' => $transactions,
			'planets_data' => $basesData,
			'investments_data' => $this->getInvestmentsData(
				$currentPlayer,
				$playerPlanets,
				$commanders,
				$transactions,
				$basesData,
				$taxCoeff,
			),
		]);
	}

	/**
	 * @param list<Planet> $bases
	 *
	 * @return array<string, array{
	 *     tax_income: int,
	 *     tax_income_bonus: int,
	 *     routes: list<CommercialRoute>,
	 *     routes_count: int,
	 *     routes_income: int,
	 *     routes_income_bonus: int,
	 * }>
	 */
	private function getBasesData(array $bases, int $taxCoeff): array
	{
		return array_reduce($bases, function (array $carry, Planet $base) {
			$routesIncome = $this->commercialRouteRepository->getPlanetIncome($base);
			$populationTax = $this->populationTaxHandler->getPopulationTax($base);

			$carry[$base->id->toRfc4122()] = [
				'tax_income' => $populationTax->initial,
				'tax_income_bonus' => $populationTax->bonus,
				// @TODO possible non pertinent retrieval of bases count. Why filtering by statement for the count and not for the retrieval ?
				'routes' => $this->commercialRouteRepository->getPlanetRoutes($base),
				'routes_count' => $this->commercialRouteRepository->countPlanetRoutes($base, [CommercialRoute::ACTIVE]),
				'routes_income' => $routesIncome,
				'routes_income_bonus' => $this->bonusApplier->apply($routesIncome, PlayerBonusId::COMMERCIAL_INCOME),
			];

			return $carry;
		}, []);
	}

	/**
	 * @param Planet[]      $playerBases
	 * @param Commander[]   $commanders
	 * @param Transaction[] $transactions
	 * @param array<string, array{
	 *      tax_income: int,
	 *      tax_income_bonus: int,
	 *      routes: list<CommercialRoute>,
	 *      routes_count: int,
	 *      routes_income: int,
	 *      routes_income_bonus: int,
	 *  }> $basesData
	 *
	 * @return array<string, int>
	 */
	private function getInvestmentsData(
		Player $player,
		array $playerBases,
		array $commanders,
		array $transactions,
		array $basesData,
		int $taxCoeff,
	): array {
		$data = [
			'totalTaxIn' => 0,
			'totalTaxInBonus' => 0,
			'totalRouteIncome' => 0,
			'totalInvest' => 0,
			'totalInvestUni' => $player->iUniversity,
			'totalFleetFees' => 0,
			'totalShipsFees' => 0,
			'totalTaxOut' => 0,
			'totalMSFees' => 0,
		];

		foreach ($playerBases as $base) {
			$populationTax = $this->populationTaxHandler->getPopulationTax($base);
			$data['totalTaxIn'] += $populationTax->initial;
			$data['totalTaxInBonus'] += $populationTax->bonus;
			$data['totalTaxOut'] += $populationTax->getTotal() * $base->system->sector->tax / 100;
			$data['totalInvest'] += $base->iSchool + $base->iAntiSpy;
			$data['totalShipsFees'] += ($this->calculateFleetCost)($base->getShipStorage(), false);

			// @TODO cout des trucs en vente

			foreach ($basesData[$base->id->toRfc4122()]['routes'] as $route) {
				if (CommercialRoute::ACTIVE == $route->statement) {
					$data['totalRouteIncome'] += $route->income;
				}
			}
		}

		foreach ($commanders as $commander) {
			$data['totalFleetFees'] += $commander->level * Commander::LVLINCOMECOMMANDER;
			$data['totalShipsFees'] += ($this->calculateFleetCost)($commander->getNbrShipByType());
		}

		foreach ($transactions as $transaction) {
			$data['totalShipsFees'] += ($this->getShipCategoriesConfiguration)($transaction->identifier, 'cost') * $this->shipCostReduction * $transaction->quantity;
		}

		$data['totalRouteIncomeBonus'] = $this->bonusApplier->apply(
			$data['totalRouteIncome'],
			PlayerBonusId::COMMERCIAL_INCOME
		);
		$data['total_income'] = $data['totalTaxIn']
			+ $data['totalTaxInBonus']
			+ $data['totalRouteIncome']
			+ $data['totalRouteIncomeBonus'];
		$data['total_expenses'] = $data['totalInvest']
			+ $data['totalInvestUni']
			+ $data['totalTaxOut']
			+ $data['totalMSFees']
			+ $data['totalFleetFees']
			+ $data['totalShipsFees'];

		$data['gains'] = $data['total_income'] - $data['total_expenses'];
		$data['remains'] = round($player->getCredits()) + round($data['gains']);

		return $data;
	}
}
