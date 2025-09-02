<?php

namespace App\Modules\Artemis\Application\Handler;

use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Uid\Uuid;

#[AutoconfigureTag('app.spying.handler')]
abstract readonly class SpyingHandler
{
	abstract protected function processSpyingMission(SpyReport $spyReport): void;

	abstract protected function getAntiSpyCoeff(Planet $place): int;

	public function spy(Planet $place, Player $player, int $price): SpyReport
	{
		$antiSpy = $this->getAntiSpyCoeff($place);

		$successRate = $this->calculateSuccessRate($antiSpy, $price);

		$sr = new SpyReport(
			id: Uuid::v4(),
			player: $player,
			place: $place,
			price: $price,
			placeFaction: $place->player->faction,
			placeType: $place->getType(),
			baseType: $place->typeOfBase ?? Planet::BASE_TYPE_COLONY,
			placeName: $place->name ?? 'Planète rebelle',
			points: $place->points ?? 0,
			targetPlayer: $place->player,
			targetPlayerLevel: $place->player->level,
			resources: 0,
			shipStorage: $place->getShipStorage() ?? [],
			antiSpyInvest: $place->iAntiSpy ?? 0,
			commercialRouteIncome: 0,
			commanders: [],
			successRate: $successRate,
			type: $this->getTypeOfSpy($successRate, $antiSpy),
			createdAt: new \DateTimeImmutable(),
		);

		$this->processSpyingMission($sr);

		return $sr;
	}

	protected function calculateSuccessRate(int $antiSpy, int $priceInvested): int
	{
		// spy success must be between 0 and 100
		$antiSpy = 0 == $antiSpy ? 1 : $antiSpy;
		$ratio = $priceInvested / $antiSpy;
		$percent = intval(round($ratio * 33));
		// ça veut dire qu'il payer 3x plus que ce que le gars investi pour tout voir
		if ($percent > 100) {
			$percent = 100;
		}

		return $percent;
	}

	/**
	 * @return SpyReport::TYP_*
	 */
	protected function getTypeOfSpy(int $success, int $antiSpy): int
	{
		if ($antiSpy < 1000) {
			return SpyReport::TYP_NOT_CAUGHT;
		}

		$percent = random_int(0, 100);
		if ($success < 40) {
			if ($percent < 5) {
				return SpyReport::TYP_NOT_CAUGHT;			// 5%
			} elseif ($percent < 50) {
				return SpyReport::TYP_ANONYMOUSLY_CAUGHT;	// 45%
			} else {
				return SpyReport::TYP_CAUGHT;				// 50%
			}
		} elseif ($success < 80) {
			if ($percent < 30) {
				return SpyReport::TYP_NOT_CAUGHT;			// 30%
			} elseif ($percent < 60) {
				return SpyReport::TYP_ANONYMOUSLY_CAUGHT;	// 30%
			} else {
				return SpyReport::TYP_CAUGHT;				// 40%
			}
		} elseif ($success < 100) {
			if ($percent < 50) {
				return SpyReport::TYP_NOT_CAUGHT;			// 50%
			} elseif ($percent < 80) {
				return SpyReport::TYP_ANONYMOUSLY_CAUGHT;	// 30%
			} else {
				return SpyReport::TYP_CAUGHT;				// 20%
			}
		} else { // success == 100
			if ($percent < 70) {
				return SpyReport::TYP_NOT_CAUGHT;			// 70%
			} elseif ($percent < 90) {
				return SpyReport::TYP_ANONYMOUSLY_CAUGHT;	// 20%
			} else {
				return SpyReport::TYP_CAUGHT;				// 10%
			}
		}
	}
}
