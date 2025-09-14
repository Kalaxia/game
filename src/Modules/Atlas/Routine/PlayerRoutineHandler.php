<?php

namespace App\Modules\Atlas\Routine;

use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Atlas\Domain\Repository\PlayerRankingRepositoryInterface;
use App\Modules\Atlas\Model\PlayerRanking;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\DBAL\Result;
use Symfony\Component\Uid\Uuid;

class PlayerRoutineHandler
{
	protected array $results = [];

	public const COEF_RESOURCE = 0.001;

	public function __construct(
		private readonly PlayerRepositoryInterface        $playerRepository,
		private readonly PlayerRankingRepositoryInterface $playerRankingRepository,
		private readonly GetShipCategoriesConfiguration   $getShipCategoriesConfiguration,
	) {
	}

	public function process(Ranking $ranking): void
	{
		$players = $this->playerRepository->getByStatements([Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]);

		$this->execute(
			$players,
			$this->playerRankingRepository->getPlayersResources(),
			$this->playerRankingRepository->getPlayersResourcesData(),
			$this->playerRankingRepository->getPlayersGeneralData(),
			$this->playerRankingRepository->getPlayersArmiesData(),
			$this->playerRankingRepository->getPlayersPlanetData(),
			$this->playerRankingRepository->getPlayersTradeRoutes(),
			$this->playerRankingRepository->getPlayersLinkedTradeRoutes(),
			$this->playerRankingRepository->getAttackersButcherRanking(),
			$this->playerRankingRepository->getDefendersButcherRanking(),
		);

		$this->processResults($ranking, $players);
	}

	/**
	 * @param list<Player> $players
	 */
	private function execute(
		array $players,
		Result $resourcesStatement,
		Result $resourcesDataStatement,
		Result $generalStatement,
		Result $armiesStatement,
		Result $planetStatement,
		Result $tradeRoutesStatement,
		Result $linkedTradeRoutesStatement,
		Result $attackersStatement,
		Result $defendersStatement,
	): void {
		$this->results = [];
		// create an array with all the players
		foreach ($players as $player) {
			$this->results[$player->id] = [
				'general' => 0,
				'resources' => 0,
				'experience' => 0,
				'victory' => 0,
				'defeat' => 0,
				'fight' => 0,
				'armies' => 0,
				'butcher' => 0,
				'butcherDestroyedPEV' => 0,
				'butcherLostPEV' => 0,
				'trader' => 0,

				'DA_Resources' => 0,
				'DA_PlanetNumber' => 0,
			];
		}
		$this->calculateDataResources($resourcesDataStatement);
		$this->calculatePlanetRanking($planetStatement);
		$this->calculateGeneralRanking($generalStatement);
		$this->calculateArmiesRanking($armiesStatement);
		$this->calculateTradeRanking($tradeRoutesStatement, $linkedTradeRoutesStatement);
		$this->calculateButcherRanking($attackersStatement, $defendersStatement);
	}

	/**
	 * @param list<Player> $players
	 */
	private function processResults(Ranking $ranking, $players): void
	{
		foreach ($players as $player) {
			if (isset($this->results[$player->id])) {
				// add the points to the list
				$this->results[$player->id]['experience'] += $player->experience;
				$this->results[$player->id]['victory'] += $player->victory;
				$this->results[$player->id]['defeat'] += $player->defeat;
				$this->results[$player->id]['fight'] += $player->victory - $player->defeat;
			}
		}

		// copy the arrays
		$listG = $this->results;
		$listR = $this->results;
		$listE = $this->results;
		$listF = $this->results;
		$listA = $this->results;
		$listB = $this->results;
		$listT = $this->results;

		// sort all the copies
		uasort($listG, [$this, 'cmpGeneral']);
		uasort($listR, [$this, 'cmpResources']);
		uasort($listE, [$this, 'cmpExperience']);
		uasort($listF, [$this, 'cmpFight']);
		uasort($listA, [$this, 'cmpArmies']);
		uasort($listB, [$this, 'cmpButcher']);
		uasort($listT, [$this, 'cmpTrader']);

		/*foreach ($list as $key => $value) {
			echo $key . ' => ' . $value['general'] . '<br/>';
		}*/

		// put the position in each array
		$position = 1;
		foreach ($listG as $key => $value) {
			$listG[$key]['position'] = $position++;
		}
		$position = 1;
		foreach ($listR as $key => $value) {
			$listR[$key]['position'] = $position++;
		}
		$position = 1;
		foreach ($listE as $key => $value) {
			$listE[$key]['position'] = $position++;
		}
		$position = 1;
		foreach ($listF as $key => $value) {
			$listF[$key]['position'] = $position++;
		}
		$position = 1;
		foreach ($listA as $key => $value) {
			$listA[$key]['position'] = $position++;
		}
		$position = 1;
		foreach ($listB as $key => $value) {
			$listB[$key]['position'] = $position++;
		}
		$position = 1;
		foreach ($listT as $key => $value) {
			$listT[$key]['position'] = $position++;
		}

		foreach ($players as $player) {
			$playerId = $player->id;

			/** @var PlayerRanking|null $oldRanking */
			$oldRanking = $this->playerRankingRepository->getPlayerLastRanking($player);
			$generalPosition = $listG[$playerId]['position'];
			$resourcesPosition = $listR[$playerId]['position'];
			$fightPosition = $listF[$playerId]['position'];
			$traderPosition = $listT[$playerId]['position'];
			$butcherPosition = $listB[$playerId]['position'];
			$armiesPosition = $listA[$playerId]['position'];
			$experiencePosition = $listE[$playerId]['position'];

			$pr = new PlayerRanking(
				id: Uuid::v4(),
				ranking: $ranking,
				player: $player,
				general: $listG[$playerId]['general'],
				generalPosition: $generalPosition,
				generalVariation: $oldRanking ? $oldRanking->generalPosition - $generalPosition : 0,
				experience: $listE[$playerId]['experience'],
				experiencePosition: $experiencePosition,
				experienceVariation: $oldRanking ? $oldRanking->experiencePosition - $experiencePosition : 0,
				butcher: ($listB[$playerId]['butcher'] >= 0) ? $listB[$playerId]['butcher'] : 0,
				butcherDestroyedPEV: $listB[$playerId]['butcherDestroyedPEV'],
				butcherLostPEV: $listB[$playerId]['butcherLostPEV'],
				butcherPosition: $butcherPosition,
				butcherVariation: $oldRanking ? $oldRanking->butcherPosition - $butcherPosition : 0,
				trader: $listT[$playerId]['trader'],
				traderPosition: $traderPosition,
				traderVariation: $oldRanking ? $oldRanking->traderPosition - $traderPosition : 0,
				fight: ($listF[$playerId]['fight'] >= 0) ? $listF[$playerId]['fight'] : 0,
				victories: $listF[$playerId]['victory'],
				defeat: $listF[$playerId]['defeat'],
				fightPosition: $fightPosition,
				fightVariation: $oldRanking ? $oldRanking->fightPosition - $fightPosition : 0,
				armies: $listA[$playerId]['armies'],
				armiesPosition: $armiesPosition,
				armiesVariation: $oldRanking ? $oldRanking->armiesPosition - $armiesPosition : 0,
				resources: intval(round($listR[$playerId]['resources'])),
				resourcesPosition: $resourcesPosition,
				resourcesVariation: $oldRanking ? $oldRanking->resourcesPosition - $resourcesPosition : 0,
				createdAt: new \DateTimeImmutable(),
			);

			$this->playerRankingRepository->save($pr);
		}
	}

	protected function calculateDataResources(Result $statement): void
	{
		while ($row = $statement->fetchAssociative()) {
			if (isset($this->results[$row['player']])) {
				$this->results[$row['player']]['DA_Resources'] += $row['sumResources'];
			}
		}
	}

	protected function calculatePlanetRanking(Result $statement): void
	{
		while ($row = $statement->fetchAssociative()) {
			if (isset($this->results[$row['player']])) {
				$this->results[$row['player']]['DA_PlanetNumber'] += $row['sumPlanets'];
			}
		}
	}

	protected function calculateGeneralRanking(Result $statement): void
	{
		while ($row = $statement->fetchAssociative()) {
			if (isset($this->results[$row['player']])) {
				$shipStorage = json_decode($row['ship_storage'], true, flags: JSON_THROW_ON_ERROR);

				$shipPrice = 0;
				$shipPrice += ($this->getShipCategoriesConfiguration)(0, 'resourcePrice') * ($shipStorage[0] ?? 0);
				$shipPrice += ($this->getShipCategoriesConfiguration)(1, 'resourcePrice') * ($shipStorage[1] ?? 0);
				$shipPrice += ($this->getShipCategoriesConfiguration)(2, 'resourcePrice') * ($shipStorage[2] ?? 0);
				$shipPrice += ($this->getShipCategoriesConfiguration)(3, 'resourcePrice') * ($shipStorage[3] ?? 0);
				$shipPrice += ($this->getShipCategoriesConfiguration)(4, 'resourcePrice') * ($shipStorage[4] ?? 0);
				$shipPrice += ($this->getShipCategoriesConfiguration)(5, 'resourcePrice') * ($shipStorage[5] ?? 0);
				$shipPrice += ($this->getShipCategoriesConfiguration)(6, 'resourcePrice') * ($shipStorage[6] ?? 0);
				$shipPrice += ($this->getShipCategoriesConfiguration)(7, 'resourcePrice') * ($shipStorage[7] ?? 0);
				$shipPrice += ($this->getShipCategoriesConfiguration)(8, 'resourcePrice') * ($shipStorage[8] ?? 0);
				$shipPrice += ($this->getShipCategoriesConfiguration)(9, 'resourcePrice') * ($shipStorage[9] ?? 0);
				$shipPrice += ($this->getShipCategoriesConfiguration)(10, 'resourcePrice') * ($shipStorage[10] ?? 0);
				$shipPrice += ($this->getShipCategoriesConfiguration)(11, 'resourcePrice') * ($shipStorage[11] ?? 0);
				$points = round($shipPrice * self::COEF_RESOURCE);
				$points += $row['points'];
				$points += round($row['resources'] * self::COEF_RESOURCE);
				$this->results[$row['player']]['general'] += $points;

				$pevQuantity = 0;
				$pevQuantity += ($this->getShipCategoriesConfiguration)(0, 'pev') * ($shipStorage[0] ?? 0);
				$pevQuantity += ($this->getShipCategoriesConfiguration)(1, 'pev') * ($shipStorage[1] ?? 0);
				$pevQuantity += ($this->getShipCategoriesConfiguration)(2, 'pev') * ($shipStorage[2] ?? 0);
				$pevQuantity += ($this->getShipCategoriesConfiguration)(3, 'pev') * ($shipStorage[3] ?? 0);
				$pevQuantity += ($this->getShipCategoriesConfiguration)(4, 'pev') * ($shipStorage[4] ?? 0);
				$pevQuantity += ($this->getShipCategoriesConfiguration)(5, 'pev') * ($shipStorage[5] ?? 0);
				$pevQuantity += ($this->getShipCategoriesConfiguration)(6, 'pev') * ($shipStorage[6] ?? 0);
				$pevQuantity += ($this->getShipCategoriesConfiguration)(7, 'pev') * ($shipStorage[7] ?? 0);
				$pevQuantity += ($this->getShipCategoriesConfiguration)(8, 'pev') * ($shipStorage[8] ?? 0);
				$pevQuantity += ($this->getShipCategoriesConfiguration)(9, 'pev') * ($shipStorage[9] ?? 0);
				$pevQuantity += ($this->getShipCategoriesConfiguration)(10, 'pev') * ($shipStorage[10] ?? 0);
				$pevQuantity += ($this->getShipCategoriesConfiguration)(11, 'pev') * ($shipStorage[11] ?? 0);
				$this->results[$row['player']]['armies'] += $pevQuantity;
			}
		}
	}

	protected function calculateArmiesRanking(Result $statement): void
	{
		while ($row = $statement->fetchAssociative()) {
			if (isset($this->results[$row['player']])) {
				$shipPrice = 0;
				$shipPrice += ($this->getShipCategoriesConfiguration)(0, 'resourcePrice') * $row['s0'];
				$shipPrice += ($this->getShipCategoriesConfiguration)(1, 'resourcePrice') * $row['s1'];
				$shipPrice += ($this->getShipCategoriesConfiguration)(2, 'resourcePrice') * $row['s2'];
				$shipPrice += ($this->getShipCategoriesConfiguration)(3, 'resourcePrice') * $row['s3'];
				$shipPrice += ($this->getShipCategoriesConfiguration)(4, 'resourcePrice') * $row['s4'];
				$shipPrice += ($this->getShipCategoriesConfiguration)(5, 'resourcePrice') * $row['s5'];
				$shipPrice += ($this->getShipCategoriesConfiguration)(6, 'resourcePrice') * $row['s6'];
				$shipPrice += ($this->getShipCategoriesConfiguration)(7, 'resourcePrice') * $row['s7'];
				$shipPrice += ($this->getShipCategoriesConfiguration)(8, 'resourcePrice') * $row['s8'];
				$shipPrice += ($this->getShipCategoriesConfiguration)(9, 'resourcePrice') * $row['s9'];
				$shipPrice += ($this->getShipCategoriesConfiguration)(10, 'resourcePrice') * $row['s10'];
				$shipPrice += ($this->getShipCategoriesConfiguration)(11, 'resourcePrice') * $row['s11'];
				$points = round($shipPrice * self::COEF_RESOURCE);
				$this->results[$row['player']]['general'] += $points;

				$pevQuantity = 0;
				$pevQuantity += ($this->getShipCategoriesConfiguration)(0, 'pev') * $row['s0'];
				$pevQuantity += ($this->getShipCategoriesConfiguration)(1, 'pev') * $row['s1'];
				$pevQuantity += ($this->getShipCategoriesConfiguration)(2, 'pev') * $row['s2'];
				$pevQuantity += ($this->getShipCategoriesConfiguration)(3, 'pev') * $row['s3'];
				$pevQuantity += ($this->getShipCategoriesConfiguration)(4, 'pev') * $row['s4'];
				$pevQuantity += ($this->getShipCategoriesConfiguration)(5, 'pev') * $row['s5'];
				$pevQuantity += ($this->getShipCategoriesConfiguration)(6, 'pev') * $row['s6'];
				$pevQuantity += ($this->getShipCategoriesConfiguration)(7, 'pev') * $row['s7'];
				$pevQuantity += ($this->getShipCategoriesConfiguration)(8, 'pev') * $row['s8'];
				$pevQuantity += ($this->getShipCategoriesConfiguration)(9, 'pev') * $row['s9'];
				$pevQuantity += ($this->getShipCategoriesConfiguration)(10, 'pev') * $row['s10'];
				$pevQuantity += ($this->getShipCategoriesConfiguration)(11, 'pev') * $row['s11'];
				$this->results[$row['player']]['armies'] += $pevQuantity;
			}
		}
	}

	protected function calculateTradeRanking(Result $routesStatement, Result $linkedRoutesStatement): void
	{
		while ($row = $routesStatement->fetchAssociative()) {
			if (isset($this->results[$row['player']])) {
				$this->results[$row['player']]['trader'] += $row['income'];
			}
		}
		while ($row = $linkedRoutesStatement->fetchAssociative()) {
			if (isset($this->results[$row['player']])) {
				$this->results[$row['player']]['trader'] += $row['income'];
			}
		}
	}

	protected function calculateButcherRanking(Result $attackerStatement, Result $defenderStatement): void
	{
		while ($row = $attackerStatement->fetchAssociative()) {
			if (isset($this->results[$row['player_id']])) {
				$this->results[$row['player_id']]['butcherDestroyedPEV'] += $row['destroyedPEV'];
				$this->results[$row['player_id']]['butcherLostPEV'] += $row['lostPEV'];
				$this->results[$row['player_id']]['butcher'] += $row['destroyedPEV'] - $row['lostPEV'];
			}
		}
		while ($row = $defenderStatement->fetchAssociative()) {
			if (isset($this->results[$row['player_id']])) {
				$this->results[$row['player_id']]['butcherDestroyedPEV'] += $row['destroyedPEV'];
				$this->results[$row['player_id']]['butcherLostPEV'] += $row['lostPEV'];
				$this->results[$row['player_id']]['butcher'] += $row['destroyedPEV'] - $row['lostPEV'];
			}
		}
	}

	/**
	 * @param array<string, mixed> $a
	 * @param array<string, mixed> $b
	 */
	protected function cmpGeneral(array $a, array $b): int
    {
        return $b['general'] <=> $a['general'];
    }

	/**
	 * @param array<string, mixed> $a
	 * @param array<string, mixed> $b
	 */
	protected function cmpResources(array $a, array $b): int
    {
        return $b['resources'] <=> $a['resources'];
    }

	/**
	 * @param array<string, mixed> $a
	 * @param array<string, mixed> $b
	 */
	protected function cmpExperience(array $a, array $b): int
    {
        return $b['experience'] <=> $a['experience'];
    }

	/**
	 * @param array<string, mixed> $a
	 * @param array<string, mixed> $b
	 */
	protected function cmpFight(array $a, array $b): int
    {
        return $b['fight'] <=> $a['fight'];
    }

	/**
	 * @param array<string, mixed> $a
	 * @param array<string, mixed> $b
	 */
	protected function cmpArmies(array $a, array $b): int
    {
        return $b['armies'] <=> $a['armies'];
    }

	/**
	 * @param array<string, mixed> $a
	 * @param array<string, mixed> $b
	 */
	protected function cmpButcher(array $a, array $b): int
    {
        return $b['butcher'] <=> $a['butcher'];
    }

	/**
	 * @param array<string, mixed> $a
	 * @param array<string, mixed> $b
	 */
	protected function cmpTrader(array $a, array $b): int
    {
        return $b['trader'] <=> $a['trader'];
    }
}
