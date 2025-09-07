<?php

namespace App\Modules\Atlas\Routine;

use App\Modules\Atlas\Domain\Repository\FactionRankingRepositoryInterface;
use App\Modules\Atlas\Domain\Repository\PlayerRankingRepositoryInterface;
use App\Modules\Atlas\Manager\RankingManager;
use App\Modules\Atlas\Model\FactionRanking;
use App\Modules\Atlas\Model\PlayerRanking;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

class FactionRoutineHandler
{
	/**
	 * Contains results of all alive factions.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $results = [];
	protected bool $isGameOver = false;

	public function __construct(
		private readonly ColorRepositoryInterface $colorRepository,
		private readonly DurationHandler $durationHandler,
		private readonly FactionRankingRepositoryInterface $factionRankingRepository,
		private readonly SectorRepositoryInterface $sectorRepository,
		private readonly PlayerRankingRepositoryInterface $playerRankingRepository,
		private readonly RankingManager $rankingManager,
		#[Autowire('%server_start_time%')]
		private readonly string $serverStartTime,
		#[Autowire('%hours_before_start_of_ranking%')]
		private readonly int $hoursBeforeStartOfRanking,
		#[Autowire('%points_to_win%')]
		private readonly int $pointsToWin,
	) {
	}

	public function process(Ranking $ranking): void
	{
		$factions = $this->colorRepository->getInGameFactions();
		$sectors = $this->sectorRepository->getAll();

		foreach ($factions as $faction) {
			$routesIncome = $this->factionRankingRepository->getRoutesIncome($faction);
			$playerRankings = $this->playerRankingRepository->getFactionPlayerRankings($ranking, $faction);

			$this->execute($faction, $playerRankings, $routesIncome, $sectors);
		}

		$winningFactionId = $this->processResults($ranking, $factions);

		if (null !== $winningFactionId) {
			$this->rankingManager->processWinningFaction($winningFactionId);
		}
	}

	public function execute(Color $faction, array $playerRankings, array $routesIncome, array $sectors): void
	{
		$this->results[$faction->identifier] = [
			'general' => 0,
			'wealth' => 0,
			'territorial' => 0,
			'points' => $faction->rankingPoints,
		];
		if (1 == $faction->isWinner) {
			$this->isGameOver = true;
		}
		$this->calculateGeneralRanking($playerRankings);
		$this->calculateWealthRanking($faction, $routesIncome);
		$this->calculateTerritorialRanking($faction, $sectors);
	}

	/**
	 * @param list<Color> $factions
	 */
	public function processResults(
		Ranking $ranking,
		array $factions,
	): Color|null {
		// ---------------- COMPUTING -------------------#

		// copy the arrays
		$listG = $this->results;
		$listW = $this->results;
		$listT = $this->results;

		// sort all the copies
		uasort($listG, [$this, 'cmpFactionGeneral']);
		uasort($listW, [$this, 'cmpWealth']);
		uasort($listT, [$this, 'cmpTerritorial']);

		/*foreach ($list as $key => $value) {
			echo $key . ' => ' . $value['general'] . '<br/>';
		}*/

		// put the position in each array
		$listG = $this->setPositions($listG, 'general');
		$listW = $this->setPositions($listW, 'wealth');
		$listT = $this->setPositions($listT, 'territorial');

		// -------------------------------- POINTS RANKING -----------------------------#

		// faire ce classement uniquement après x jours de jeu
		if ($this->durationHandler->getHoursDiff(new \DateTimeImmutable($this->serverStartTime), new \DateTimeImmutable()) > $this->hoursBeforeStartOfRanking) {
			// points qu'on gagne en fonction de sa place dans le classement
			$pointsToEarn = [40, 30, 20, 10, 0, 0, 0, 0, 0, 0, 0];
			$coefG = 0.1; // 4 3 2 1 0 ...
			$coefW = 0.4; // 16 12 8 4 0 ...
			$coefT = 0.5; // 20 15 10 5 0 ...

			foreach ($factions as $faction) {
				$factionId = $faction->identifier;
				$generalPosition = $listG[$factionId]['position'];
				$wealthPosition = $listW[$factionId]['position'];
				$territorialPosition = $listT[$factionId]['position'];
				$additionalPoints = 0;

				// general
				$additionalPoints += intval(floor($pointsToEarn[$generalPosition - 1] * $coefG));

				// wealth
				$additionalPoints += intval(floor($pointsToEarn[$wealthPosition - 1] * $coefW));

				// territorial
				$additionalPoints += intval(floor($pointsToEarn[$territorialPosition - 1] * $coefT));

				$this->results[$factionId]['points'] += $additionalPoints;
			}
		}

		// ---------------- LAST COMPUTING -------------------#

		$listP = $this->results;
		uasort($listP, [$this, 'cmpPoints']);

		$position = 1;
		foreach ($listP as $key => $value) {
			$listP[$key]['position'] = $position++;
		}

		// ---------------- SAVING -------------------#

		$rankings = [];

		foreach ($factions as $faction) {
			$factionId = $faction->identifier;
			/** @var FactionRanking|null $oldRanking */
			$oldRanking = $this->factionRankingRepository->getLastRanking($faction);
			$generalPosition = $listG[$factionId]['position'];
			$wealthPosition = $listW[$factionId]['position'];
			$territorialPosition = $listT[$factionId]['position'];

			if (true === $this->isGameOver) {
				$points = $oldRanking->points;
				$pointsPosition = $oldRanking->pointsPosition;
				$pointsVariation = 0;
				$newPoints = 0;
			} else {
				$points = $listP[$factionId]['points'];
				$pointsPosition = $listP[$factionId]['position'];
				$pointsVariation = $oldRanking ? $oldRanking->pointsPosition - $pointsPosition : 0;
				$newPoints = $oldRanking ? $points - $oldRanking->points : $points;
			}

			$fr = new FactionRanking(
				id: Uuid::v4(),
				ranking: $ranking,
				faction: $faction,
				points: $points,
				pointsPosition: $pointsPosition,
				pointsVariation: $pointsVariation,
				newPoints: $newPoints,
				general: $listG[$factionId]['general'],
				generalPosition: $generalPosition,
				generalVariation: $oldRanking ? $oldRanking->generalPosition - $generalPosition : 0,
				wealth: $listW[$factionId]['wealth'],
				wealthPosition: $wealthPosition,
				wealthVariation: $oldRanking ? $oldRanking->wealthPosition - $wealthPosition : 0,
				territorial: $listT[$factionId]['territorial'],
				territorialPosition: $territorialPosition,
				territorialVariation: $oldRanking ? $oldRanking->territorialPosition - $territorialPosition : 0,
				createdAt: new \DateTimeImmutable(),
			);

			$rankings[] = $fr;
			$this->factionRankingRepository->save($fr);
		}

		if (false === $this->isGameOver) {
			// check if a faction wins the game
			$winRanking = null;
			foreach ($rankings as $ranking) {
				if ($ranking->points >= $this->pointsToWin) {
					if (null !== $winRanking) {
						if ($winRanking->points < $ranking->points) {
							return $ranking->faction;
						}
					} else {
						return $ranking->faction;
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param list<PlayerRanking> $playerRankings
	 */
	protected function calculateGeneralRanking(array $playerRankings): void
	{
		foreach ($playerRankings as $playerRanking) {
			$player = $playerRanking->player;

			$this->results[$player->faction->identifier]['general'] += $playerRanking->general;
		}
	}

	/**
	 * @param array{income: int|null} $routesIncome
	 */
	protected function calculateWealthRanking(Color $faction, array $routesIncome): void
	{
		$this->results[$faction->identifier]['wealth'] = $routesIncome['income'] ?? 0;
	}

	/**
	 * @param list<Sector> $sectors
	 */
	protected function calculateTerritorialRanking(Color $faction, array $sectors): void
	{
		foreach ($sectors as $sector) {
			if ($sector->faction?->id->equals($faction->id)) {
				$this->results[$sector->faction->identifier]['territorial'] += $sector->points;
			}
		}
	}

	protected function cmpFactionGeneral(array $a, array $b): int
    {
        return $b['general'] <=> $a['general'];
    }

	protected function cmpWealth(array $a, array $b): int
    {
        return $b['wealth'] <=> $a['wealth'];
    }

	protected function cmpTerritorial(array $a, array $b): int
    {
        return $b['territorial'] <=> $a['territorial'];
    }

	protected function cmpPoints(array $a, array $b): int
    {
        return $b['points'] <=> $a['points'];
    }

	protected function setPositions(array $list, $attribute): array
	{
		$position = 1;
		$index = 1;
		$previous = PHP_INT_MAX;
		foreach ($list as $key => $value) {
			if ($previous > $value[$attribute]) {
				$position = $index;
			}
			$list[$key]['position'] = $position;
			++$index;
			$previous = $list[$key][$attribute];
		}

		return $list;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function getResults(): array
	{
		return $this->results;
	}
}
