<?php

declare(strict_types=1);

namespace App\Modules\Promethee\Domain\Service;

use App\Classes\Library\Game;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class GetTimeCost
{
	public function __construct(
		private CurrentPlayerRegistry $currentPlayerRegistry,
		private CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		private TechnologyHelper $technologyHelper,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {

	}

	public function __invoke(int $identifier, int $targetLevel, int $scienceCoeff, Player|null $researcher = null): int
	{
		$researcher ??= $this->currentPlayerRegistry->get();
		$time = $this->timeMode->isStandard()
			? $this->technologyHelper->getInfo($identifier, 'time', $targetLevel)
			: 10 * $targetLevel;

		$bonusPercent = $this->currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::TECHNOSPHERE_SPEED);
		/*if (ColorResource::APHERA === $researcher->faction->identifier) {
			$bonusPercent += ColorResource::BONUS_APHERA_TECHNO;
		}*/
		// ajout du bonus du lieu
		$bonusPercent += Game::getImprovementFromScientificCoef($scienceCoeff);
		$bonus = PercentageApplier::toFloat($time, $bonusPercent);

		return intval(round($time - $bonus));
	}
}
