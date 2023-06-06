<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Library\Format;
use App\Classes\Redis\RedisManager;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Repository\LiveReportRepositoryInterface;
use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
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
		ColorManager                         $colorManager,
		CommanderRepositoryInterface         $commanderRepository,
		CommercialRouteManager               $commercialRouteManager,
		CommercialTaxRepositoryInterface     $commercialTaxRepository,
		CreditTransactionRepositoryInterface $creditTransactionRepository,
		LiveReportRepositoryInterface        $reportRepository,
		FactionRankingRepositoryInterface    $factionRankingRepository,
		GalaxyConfiguration                  $galaxyConfiguration,
		SectorRepositoryInterface            $sectorRepository,
	): Response {
		$faction = $currentPlayer->faction;

		$rankings = $factionRankingRepository->getFactionRankings($faction);

		$creditBase = 0;
		
		foreach ($rankings as $ranking) {
			if ($creditBase < $ranking->wealth) {
				$creditBase = $ranking->wealth;
			}
		}
		// @TODO factoriser cette formule
		$creditBase += $creditBase * 12 / 100;

		// load
		$creditTransactionRepository->newSession();
		$creditTransactionRepository->load(
			['rReceiver' => $faction->id, 'type' => CreditTransaction::TYP_FACTION],
			['dTransaction', 'DESC'],
			[0, 20],
		);
		$membersDonations = $creditTransactionRepository->getAll();

		$creditTransactionRepository->newSession();
		$creditTransactionRepository->load(
			['rSender' => $faction->id, 'type' => CreditTransaction::TOTO],
			['dTransaction', 'DESC'],
			[0, 20],
		);
		$factionDonations = $creditTransactionRepository->getAll();

		$importaxes = $commercialTaxRepository->getFactionTaxesByImport($faction);

		$exportTaxes = $commercialTaxRepository->getFactionTaxesByExport($faction);

		$commanderStats = $commanderRepository->getFactionCommanderStats($faction);
		$fleetStats = $commanderRepository->getFactionFleetStats($faction);

		$totalPEV = 0;
		for ($i = 0; $i < 12; ++$i) {
			$totalPEV += $fleetStats['nbs'.$i] * ShipResource::getInfo($i, 'pev');
		}

		$factions = $this->colorRepository->findAll();
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
	 * @return array<string, array>
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
				$scores = unserialize($this->redisManager->getConnection()->get('sector:'.$sector->identifier));

				if (!isset($scores[$faction->identifier]) && $sector->faction->id !== $faction->id) {
					unset($sectors[$key]);
					continue;
				}
				if ('Secteurs conquis' === $type && $sector->faction->id !== $faction->id) {
					continue;
				}

				foreach ($factions as $f) {
					if (0 === $f->id || !isset($scores[$f->identifier])) {
						continue;
					}
					$percents['color'.$f->id] = round(Format::percent($scores[$f->identifier], array_sum($scores), false));
				}

				arsort($percents);

				if ($sector->faction->id === $faction->id || ($scores[$faction->identifier] > 0)) {
					$types[$type] = $sector;
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
					$this->colorRepository->get($factionId),
				);

				return $acc;
			},
			[],
		);
	}
}
