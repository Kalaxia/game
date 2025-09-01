<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Factory;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Ares\Model\LiveReport;
use App\Modules\Ares\Model\Report;
use App\Modules\Gaia\Domain\Entity\Place;
use Symfony\Component\Uid\Uuid;

readonly class ReportFactory
{
	public function __construct(private GetShipCategoriesConfiguration $getShipCategoriesConfiguration)
	{
	}

	/**
	 * TODO Avoid having LiveReport using static
	 */
	public function create(Place $place): Report
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
		$this->setArmies();
		$this->setPev();

		return $report;
	}


	public function setPev(Report $report): void
	{
		foreach (ShipCategory::cases() as $shipCategory) {
			$report->attackerPevAtBeginning += ($this->getShipCategoriesConfiguration)($shipCategory, 'pev') * $report->attackerTotalInBegin[$shipCategory->value];
			$report->defenderPevAtBeginning += ($this->getShipCategoriesConfiguration)($shipCategory, 'pev') * $report->defenderTotalInBegin[$shipCategory->value];
			$report->attackerPevAtEnd += ($this->getShipCategoriesConfiguration)($shipCategory, 'pev') * $report->attackerTotalAtEnd[$shipCategory->value];
			$report->defenderPevAtEnd += ($this->getShipCategoriesConfiguration)($shipCategory, 'pev') * $report->defenderTotalAtEnd[$shipCategory->value];
		}
	}

	public function setArmies(Report $report): void
	{
		if ($report->armiesDone) {
			return;
		}
		// squadron(id, pos, rReport, round, rCommander, ship0, ..., ship11)

		foreach ($report->squadrons as $sq) {
			// TODO Handle differently this weird way to get differences between armies
			// First round: squadrons are added in the begin army
			if (0 == $sq[3]) {
				if ($report->attackerCommander->id->equals($sq[4]->id)) {
					$report->attackerArmyInBegin[] = $sq;
				} else {
					$report->defenderArmyInBegin[] = $sq;
				}
				// Later rounds, the fight array seems to contain squadron duplicates for storing the state evolution
			} elseif ($sq[3] > 0) {
				$report->fight[] = $sq;
				// End army is stored, Round value is -1 at this point
			} else {
				if ($report->attackerCommander->id->equals($sq[4]->id)) {
					$report->attackerArmyAtEnd[] = $sq;
				} else {
					$report->defenderArmyAtEnd[] = $sq;
				}
			}
		}

		// TODO Maybe we can stack differently the total ships.
		foreach ($report->attackerArmyInBegin as $sq) {
			for ($i = 5; $i <= 16; ++$i) {
				$report->attackerTotalInBegin[$i - 5] += $sq[$i];
			}
		}
		foreach ($report->defenderArmyInBegin as $sq) {
			for ($i = 5; $i <= 16; ++$i) {
				$report->defenderTotalInBegin[$i - 5] += $sq[$i];
			}
		}
		foreach ($report->attackerArmyAtEnd as $sq) {
			for ($i = 5; $i <= 16; ++$i) {
				$report->attackerTotalAtEnd[$i - 5] += $sq[$i];
			}
		}
		foreach ($report->defenderArmyAtEnd as $sq) {
			for ($i = 5; $i <= 16; ++$i) {
				$report->defenderTotalAtEnd[$i - 5] += $sq[$i];
			}
		}

		for ($i = 0; $i < 12; ++$i) {
			$report->attackerDifference[$i] = $report->attackerTotalInBegin[$i] - $report->attackerTotalAtEnd[$i];
		}
		for ($i = 0; $i < 12; ++$i) {
			$report->defenderDifference[$i] = $report->defenderTotalInBegin[$i] - $report->defenderTotalAtEnd[$i];
		}

		$report->armiesDone = true;
	}
}
