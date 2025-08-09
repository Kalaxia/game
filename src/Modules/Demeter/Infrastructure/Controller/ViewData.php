<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Library\Format;
use App\Classes\Redis\RedisManager;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Repository\LiveReportRepositoryInterface;
use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Domain\Repository\SquadronRepositoryInterface;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Resource\CommanderResources;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Domain\Repository\CommercialTaxRepositoryInterface;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Atlas\Domain\Repository\FactionRankingRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Galaxy\GalaxyConfiguration;
use App\Modules\Gaia\Manager\SectorManager;
use App\Modules\Gaia\Model\Sector;
use App\Modules\Zeus\Domain\Repository\CreditTransactionRepositoryInterface;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// @TODO split this controller into multiple ones
class ViewData extends AbstractController
{
	public function __construct(
		private readonly CommercialRouteRepositoryInterface $commercialRouteRepository,
		private readonly ColorRepositoryInterface $colorRepository,
		private readonly RedisManager $redisManager,
	) {

	}

	public function __invoke(
		Request                              $request,
		Player                               $currentPlayer,
		CommanderRepositoryInterface         $commanderRepository,
		CommercialTaxRepositoryInterface     $commercialTaxRepository,
		CreditTransactionRepositoryInterface $creditTransactionRepository,
		LiveReportRepositoryInterface        $reportRepository,
		FactionRankingRepositoryInterface    $factionRankingRepository,
		GalaxyConfiguration                  $galaxyConfiguration,
		SectorRepositoryInterface            $sectorRepository,
		SquadronRepositoryInterface $squadronRepository,
		GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	): Response {
		$faction = $currentPlayer->faction
			?? throw new \LogicException('Player must have a faction');

		$rankings = $factionRankingRepository->getFactionRankings($faction);

		$creditBase = 0;
		
		foreach ($rankings as $ranking) {
			if ($creditBase < $ranking->wealth) {
				$creditBase = $ranking->wealth;
			}
		}
		// @TODO factoriser cette formule
		$creditBase += $creditBase * 12 / 100;

		$membersDonations = $creditTransactionRepository->getAllByFactionReceiverFromMembers($faction);

		$factionDonations = $creditTransactionRepository->getAllBySender($faction);

		$importaxes = $commercialTaxRepository->getFactionTaxesByImport($faction);

		$exportTaxes = $commercialTaxRepository->getFactionTaxesByExport($faction);

		$commanderStats = $commanderRepository->getFactionCommanderStats($faction);

		$fleetStats = $squadronRepository->getFactionFleetStats($faction);

		$totalPEV = 0;
		for ($i = 0; $i < 12; ++$i) {
			$totalPEV += ($fleetStats['nbs'.$i]) * $getShipCategoriesConfiguration($i, 'pev');
		}

		$factions = $this->colorRepository->getAll();
		$sectors = $sectorRepository->getAll();
		$mapData = $this->getTacticalMapData($faction, $factions, $sectors);

		return $this->render('pages/demeter/faction/data.html.twig', [
			'faction' => $faction,
			'credit_base' => $creditBase,
			'faction_rankings' => $rankings,
			'members_donations' => $membersDonations,
			'faction_donations' => $factionDonations,
			'faction_sectors' => $sectorRepository->getFactionSectors($faction),
			'rc_data' => $this->commercialRouteRepository->getCommercialRouteFactionData($faction),
			'faction_internal_commercial_routes_data' => $this->commercialRouteRepository->getInternalCommercialRouteFactionData($faction),
			'rc_diplomatic_data' => $this->getCommercialRoutesDiplomaticData($faction),
			'import_taxes' => $importaxes,
			'export_taxes' => $exportTaxes,
			'commanders_ranks_count' => CommanderResources::size(),
			'base_commander_level' => Commander::CMDBASELVL,
			'commander_stats' => $commanderStats,
			'fleet_stats' => $fleetStats,
			'total_pev' => $totalPEV,
			'attack_reports' => $reportRepository->getFactionAttackReports($faction),
			'defense_reports' => $reportRepository->getFactionDefenseReports($faction),
			'sectors' => $sectors,
			'map_scale' => 750 / $galaxyConfiguration->galaxy['size'],
			'map_data_types' => $mapData['types'],
			'map_data_scores' => $mapData['scores'],
			'map_data_percents' => $mapData['percents'],
			'galaxy_configuration' => $galaxyConfiguration,
			'factions' => $factions,
			'points_to_win' => $this->getParameter('points_to_win'),
			'diplomacy_statements' => [
				Color::ENEMY => 'En guerre',
				Color::ALLY => 'Allié',
				Color::PEACE => 'Pacte de non-agression',
				Color::NEUTRAL => 'Neutre',
			],
			'laws_count' => LawResources::size(),
		]);
	}

	/**
	 * @param list<Color> $factions
	 * @param list<Sector> $sectors
	 * @return array{
	 *     types: array<string, list<Sector>>,
	 *     scores: array<int, int>,
	 *     percents: array<string, float>,
	 * }
	 * @throws \RedisException
	 */
	private function getTacticalMapData(Color $faction, array $factions, array $sectors): array
	{
		$scores = $percents = [];

		$types = [
			'Secteurs conquis' => [],
			'Secteurs en balance' => [],
		];

		foreach (array_keys($types) as $type) {
			foreach ($sectors as $key => $sector) {
				$percents = ['color'.$faction->identifier => 0];
				$sectorOwnershipScores = $this->redisManager->getConnection()->get('sector:' . $sector->id);
				if (!is_string($sectorOwnershipScores)) {
					throw new \LogicException(sprintf('Missing ownership data for sector %s', $sector->id));
				}

				/** @var array<int, int> $scores */
				$scores = unserialize($sectorOwnershipScores);

				if (!isset($scores[$faction->identifier]) && !$sector->faction?->id->equals($faction->id)) {
					unset($sectors[$key]);
					continue;
				}
				if ('Secteurs conquis' === $type && !$sector->faction?->id->equals($faction->id)) {
					continue;
				}

				foreach ($factions as $f) {
					if (0 === $f->identifier || !isset($scores[$f->identifier])) {
						continue;
					}
					$percents['color'.$f->identifier] = round(Format::percent($scores[$f->identifier], array_sum($scores), false));
				}

				arsort($percents);

				if (null === $sector->faction) {
					continue;
				}

				if ($sector->faction->id->equals($faction->id) || ($scores[$faction->identifier] > 0)) {
					$types[$type][] = $sector;
				}
			}
		}

		return [
			'types' => $types,
			'scores' => $scores,
			'percents' => $percents,
		];
	}

	/**
	 * @return array<int, int>
	 */
	private function getCommercialRoutesDiplomaticData(Color $faction): array
	{
		return array_reduce(
			array_filter(array_keys($faction->relations), fn (int $factionId) => !in_array($factionId, [0, $faction->identifier])),
			function ($acc, $factionId) use ($faction) {
				$acc[$factionId] = $this->commercialRouteRepository->countCommercialRoutesBetweenFactions(
					$faction,
					$this->colorRepository->getOneByIdentifier($factionId),
				);

				return $acc;
			},
			[],
		);
	}
}
