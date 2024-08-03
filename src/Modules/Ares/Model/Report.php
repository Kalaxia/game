<?php

namespace App\Modules\Ares\Model;

use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Gaia\Model\Place;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class Report
{
	public const STANDARD = 0;
	public const ARCHIVED = 1;
	public const DELETED = 2;

	public const ILLEGAL = 0;
	public const LEGAL = 1;

	public array $attackerArmyInBegin = [];
	public array $defenderArmyInBegin = [];
	public array $attackerArmyAtEnd = [];
	public array $defenderArmyAtEnd = [];
	public array $fight = [];
	public array $attackerTotalInBegin = [0,0,0,0,0,0,0,0,0,0,0,0];
	public array $defenderTotalInBegin = [0,0,0,0,0,0,0,0,0,0,0,0];
	public array $attackerTotalAtEnd = [0,0,0,0,0,0,0,0,0,0,0,0];
	public array $defenderTotalAtEnd = [0,0,0,0,0,0,0,0,0,0,0,0];
	public array $attackerDifference = [0,0,0,0,0,0,0,0,0,0,0,0];
	public array $defenderDifference = [0,0,0,0,0,0,0,0,0,0,0,0];
	public bool $armiesDone = false;
	public int $attackerPevAtBeginning = 0;
	public int $defenderPevAtBeginning = 0;
	public int $attackerPevAtEnd = 0;
	public int $defenderPevAtEnd = 0;
	public int $attackerStatement = self::STANDARD;
	public int $defenderStatement = self::STANDARD;
	public bool $hasBeenPunished = false;

	/**
	 * @param array $squadrons
	 */
	public function __construct(
		public Uuid $id,
		public Player $attacker,
		public Player|null $defender,
		public Player|null $winner,
		public Commander|null $attackerCommander,
		public Commander|null $defenderCommander,
		public Place $place,
		public int $type,
		public int $attackerLevel,
		public int $defenderLevel,
		public int $attackerExperience,
		public int $defenderExperience,
		public int $attackerPalmares,
		public int $defenderPalmares,
		public int $resources,
		public int $attackerCommanderExperience,
		public int $defenderCommanderExperience,
		public int $earnedExperience,
		public bool $isLegal,
		public int $round,
		public \DateTimeImmutable $foughtAt,
		public array $squadrons,
	) {
	}

	public static function fromLiveReport(Place $place): static
	{
		$report = new Report(
			id: Uuid::v4(),
			attacker: LiveReport::$rPlayerAttacker,
			defender: LiveReport::$rPlayerDefender,
			winner: LiveReport::$rPlayerWinner,
			attackerCommander: LiveReport::$attackerCommander,
			// Ensures we don't save a virtual commander
			defenderCommander: null !== LiveReport::$rPlayerDefender ? LiveReport::$defenderCommander : null,
			place: $place,
			type: LiveReport::$type,
			attackerLevel: LiveReport::$levelA,
			defenderLevel: LiveReport::$levelD,
			attackerExperience: LiveReport::$expPlayerA,
			defenderExperience: LiveReport::$experienceD,
			attackerPalmares: LiveReport::$palmaresA,
			defenderPalmares: LiveReport::$palmaresD,
			resources: LiveReport::$resources,
			attackerCommanderExperience: LiveReport::$experienceA,
			defenderCommanderExperience: LiveReport::$expPlayerD,
			earnedExperience: LiveReport::$expCom,
			isLegal: LiveReport::$isLegal,
			round: LiveReport::$round,
			foughtAt: LiveReport::$dFight,
			squadrons: LiveReport::$squadrons,
		);
		$report->setArmies();
		$report->setPev();

		return $report;
	}

	public function setPev(): void
	{
		for ($i = 0; $i < 12; ++$i) {
			$this->attackerPevAtBeginning += ShipResource::getInfo($i, 'pev') * $this->attackerTotalInBegin[$i];
			$this->defenderPevAtBeginning += ShipResource::getInfo($i, 'pev') * $this->defenderTotalInBegin[$i];
			$this->attackerPevAtEnd += ShipResource::getInfo($i, 'pev') * $this->attackerTotalAtEnd[$i];
			$this->defenderPevAtEnd += ShipResource::getInfo($i, 'pev') * $this->defenderTotalAtEnd[$i];
		}
	}

	public function setArmies(): void
	{
		if (!$this->armiesDone) {
			// squadron(id, pos, rReport, round, rCommander, ship0, ..., ship11)

			foreach ($this->squadrons as $sq) {
				// TODO Handle differently this weird way to get differences between armies
				// First round: squadrons are added in the begin army
				if (0 == $sq[3]) {
					if ($this->attackerCommander->id->equals($sq[4]->id)) {
						$this->attackerArmyInBegin[] = $sq;
					} else {
						$this->defenderArmyInBegin[] = $sq;
					}
				// Later rounds, the fight array seems to contain squadron duplicates for storing the state evolution
				} elseif ($sq[3] > 0) {
					$this->fight[] = $sq;
				// End army is stored, Round value is -1 at this point
				} else {
					if ($this->attackerCommander->id->equals($sq[4]->id)) {
						$this->attackerArmyAtEnd[] = $sq;
					} else {
						$this->defenderArmyAtEnd[] = $sq;
					}
				}
			}

			// TODO Maybe we can stack differently the total ships.
			foreach ($this->attackerArmyInBegin as $sq) {
				for ($i = 5; $i <= 16; ++$i) {
					$this->attackerTotalInBegin[$i - 5] += $sq[$i];
				}
			}
			foreach ($this->defenderArmyInBegin as $sq) {
				for ($i = 5; $i <= 16; ++$i) {
					$this->defenderTotalInBegin[$i - 5] += $sq[$i];
				}
			}
			foreach ($this->attackerArmyAtEnd as $sq) {
				for ($i = 5; $i <= 16; ++$i) {
					$this->attackerTotalAtEnd[$i - 5] += $sq[$i];
				}
			}
			foreach ($this->defenderArmyAtEnd as $sq) {
				for ($i = 5; $i <= 16; ++$i) {
					$this->defenderTotalAtEnd[$i - 5] += $sq[$i];
				}
			}

			for ($i = 0; $i < 12; ++$i) {
				$this->attackerDifference[$i] = $this->attackerTotalInBegin[$i] - $this->attackerTotalAtEnd[$i];
			}
			for ($i = 0; $i < 12; ++$i) {
				$this->defenderDifference[$i] = $this->defenderTotalInBegin[$i] - $this->defenderTotalAtEnd[$i];
			}

			$this->armiesDone = true;
		}
	}
}
