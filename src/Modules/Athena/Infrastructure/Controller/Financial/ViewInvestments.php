<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Financial;

use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Service\CalculateFleetCost;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Handler\Tax\PopulationTaxHandler;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewInvestments extends AbstractController
{
	public function __construct(
		private readonly BonusApplierInterface $bonusApplier,
		private readonly CommanderArmyHandler $commanderArmyHandler,
		private readonly PopulationTaxHandler $populationTaxHandler,
		private readonly CalculateFleetCost $calculateFleetCost,
	) {
	}

	public function __invoke(
		Player                         $currentPlayer,
		CommanderRepositoryInterface   $commanderRepository,
		PlanetRepositoryInterface      $planetRepository,
	): Response {
		$taxCoeff = $this->getParameter('zeus.player.tax_coeff');

		$playerPlanets = $planetRepository->getPlayerPlanets($currentPlayer);

		$commanders = $commanderRepository->getPlayerCommanders(
			$currentPlayer,
			[Commander::AFFECTED, Commander::MOVING],
			['c.base' => 'ASC'],
		);

		$basesData = $this->getBasesData($playerPlanets);

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
			'planets_data' => $basesData,
			'investments_data' => $this->getInvestmentsData(
				$currentPlayer,
				$playerPlanets,
				$commanders,
			),
		]);
	}

	/**
	 * @param list<Planet> $bases
	 * @return array<string, array{
	 *     tax_income: int,
	 *     tax_income_bonus: int,
	 * }>
	 */
	private function getBasesData(array $bases): array
	{
		return array_reduce($bases, function (array $carry, Planet $base) {
			$populationTax = $this->populationTaxHandler->getPopulationTax($base);

			$carry[$base->id->toRfc4122()] = [
				'tax_income' => $populationTax->initial,
				'tax_income_bonus' => $populationTax->bonus,
				// @TODO possible non pertinent retrieval of bases count. Why filtering by statement for the count and not for the retrieval ?
			];

			return $carry;
		}, []);
	}

	/**
	 * @param Planet[]      $playerBases
	 * @param Commander[]        $commanders
	 *
	 * @return array<string, int>
	 */
	private function getInvestmentsData(
		Player $player,
		array $playerBases,
		array $commanders,
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
		}

		foreach ($commanders as $commander) {
			$data['totalFleetFees'] += $commander->level * Commander::LVLINCOMECOMMANDER;
			$data['totalShipsFees'] += ($this->calculateFleetCost)($commander->getNbrShipByType());
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
