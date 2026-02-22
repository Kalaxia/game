<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Financial;

use App\Modules\Shared\Domain\Service\GameTimeConverter;
use App\Modules\Zeus\Domain\Repository\PlayerFinancialReportRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerFinancialReport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewReports extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		PlayerFinancialReportRepositoryInterface $playerFinancialReportRepository,
		GameTimeConverter $gameTimeConverter,
	): Response {
		$playerFinancialReports = array_reverse($playerFinancialReportRepository->getPlayerLastReports($currentPlayer));

		return $this->render('pages/athena/financial/reports.html.twig', [
			'reports_labels' => array_map(
				fn (PlayerFinancialReport $playerFinancialReport) => $gameTimeConverter->convertDatetimeToGameDate($playerFinancialReport->createdAt, false),
				$playerFinancialReports,
			),
			'reports_data' => array_reduce(
				$playerFinancialReports,
				function (array $carry, PlayerFinancialReport $playerFinancialReport) {
					$carry['populationTaxes'][] = $playerFinancialReport->populationTaxes;
					$carry['recycledCredits'][] = $playerFinancialReport->recycledCredits;
					$carry['commercialRoutesConstructions'][] = -$playerFinancialReport->commercialRoutesConstructions;
					$carry['technologiesInvestments'][] = -$playerFinancialReport->technologiesInvestments;
					$carry['receivedPlayersCreditTransactions'][] = $playerFinancialReport->receivedPlayersCreditTransactions;
					$carry['receivedFactionsCreditTransactions'][] = $playerFinancialReport->receivedFactionsCreditTransactions;
					$carry['sentPlayersCreditTransactions'][] = -$playerFinancialReport->sentPlayersCreditTransactions;
					$carry['sentFactionsCreditTransactions'][] = -$playerFinancialReport->sentFactionsCreditTransactions;
					$carry['commandersSales'][] = $playerFinancialReport->commandersSales;
					$carry['resourcesSales'][] = $playerFinancialReport->resourcesSales;
					$carry['shipsSales'][] = $playerFinancialReport->shipsSales;
					$carry['commandersPurchases'][] = -$playerFinancialReport->commandersPurchases;
					$carry['resourcesPurchases'][] = -$playerFinancialReport->resourcesPurchases;
					$carry['shipsPurchases'][] = -$playerFinancialReport->shipsPurchases;
					$carry['conquestInvestments'][] = -$playerFinancialReport->conquestInvestments;
					$carry['commercialRoutesIncome'][] = $playerFinancialReport->commercialRoutesIncome;
					$carry['factionTaxes'][] = -$playerFinancialReport->factionTaxes;
					$carry['antiSpyInvestments'][] = -$playerFinancialReport->antiSpyInvestments;
					$carry['universityInvestments'][] = -$playerFinancialReport->universityInvestments;
					$carry['schoolInvestments'][] = -$playerFinancialReport->schoolInvestments;
					$carry['commandersWages'][] = -$playerFinancialReport->commandersWages;
					$carry['shipsCost'][] = -$playerFinancialReport->shipsCost;
					$carry['diff'][] = $playerFinancialReport->getDiff();

					return $carry;
				},
				[
					'populationTaxes' => [],
					'recycledCredits' => [],
					'commercialRoutesConstructions' => [],
					'technologiesInvestments' => [],
					'receivedPlayersCreditTransactions' => [],
					'receivedFactionsCreditTransactions' => [],
					'sentPlayersCreditTransactions' => [],
					'sentFactionsCreditTransactions' => [],
					'commandersSales' => [],
					'resourcesSales' => [],
					'shipsSales' => [],
					'commandersPurchases' => [],
					'resourcesPurchases' => [],
					'shipsPurchases' => [],
					'conquestInvestments' => [],
					'commercialRoutesIncome' => [],
					'factionTaxes' => [],
					'antiSpyInvestments' => [],
					'universityInvestments' => [],
					'schoolInvestments' => [],
					'commandersWages' => [],
					'shipsCost' => [],
					'diff' => [],
				]
			),
		]);
	}
}
