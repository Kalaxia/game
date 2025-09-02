<?php

declare(strict_types=1);

namespace App\Modules\Ares\Model;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class Report
{
	public const STANDARD = 0;
	public const ARCHIVED = 1;
	public const DELETED = 2;

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
		public Uuid           $id,
		public Player         $attacker,
		public Player|null    $defender,
		public Player|null    $winner,
		public Commander|null $attackerCommander,
		public Commander|null $defenderCommander,
		public Planet         $place,
		public int            $type,
		public int            $attackerLevel,
		public int            $defenderLevel,
		public int            $attackerExperience,
		public int            $defenderExperience,
		public int            $attackerPalmares,
		public int            $defenderPalmares,
		public int            $resources,
		public int            $attackerCommanderExperience,
		public int            $defenderCommanderExperience,
		public int $earnedExperience,
		public bool $isLegal,
		public int $round,
		public \DateTimeImmutable $foughtAt,
		public array $squadrons,
	) {
	}

	public static function fromLiveReport(Planet $place): self
	{
		$report = new self(
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
}
