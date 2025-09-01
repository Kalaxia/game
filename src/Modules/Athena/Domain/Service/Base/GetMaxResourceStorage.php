<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Helper\PlanetHelper;
use App\Modules\Gaia\Resource\PlanetResource;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonusId;

readonly class GetMaxResourceStorage
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
		private PlanetHelper          $planetHelper,
		private PlayerBonusManager    $playerBonusManager,
	) {
	}

	public function __invoke(Planet $base, bool $offLimits = false): int
	{
		$playerBonus = $this->playerBonusManager->getBonusByPlayer($base->player);
		$maxStorage = $this->planetHelper->getBuildingInfo(
			PlanetResource::STORAGE,
			'level',
			$base->levelStorage,
			'storageSpace',
		);
		$maxStorage += intval(round($this->bonusApplier->apply($maxStorage, PlayerBonusId::REFINERY_STORAGE, $playerBonus)));

		if (true === $offLimits) {
			$maxStorage += Planet::EXTRA_STOCK;
		}

		return $maxStorage;
	}
}
