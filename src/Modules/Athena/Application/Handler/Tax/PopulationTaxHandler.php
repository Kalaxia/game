<?php

namespace App\Modules\Athena\Application\Handler\Tax;

use App\Modules\Athena\Domain\DTO\PopulationTax;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Resource\PlaceResource;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class PopulationTaxHandler
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
		#[Autowire('%zeus.player.tax_coeff%')]
		private float $playerTaxCoeff,
	) {
	}

	public function getPopulationTax(Planet $base): PopulationTax
	{
		$tax = ((180 * $base->place->population) + 1500) * $this->playerTaxCoeff;
		$tax *= PlaceResource::get($base->typeOfBase, 'tax');

		$bonus = intval($this->bonusApplier->apply($tax, PlayerBonusId::POPULATION_TAX));

		return new PopulationTax(
			initial: intval(round($tax)),
			bonus: $bonus,
		);
	}
}
