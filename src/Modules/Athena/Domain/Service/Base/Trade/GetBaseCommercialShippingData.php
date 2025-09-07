<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Trade;

use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Galaxy\Domain\Entity\Planet;

readonly class GetBaseCommercialShippingData
{
	public function __construct(private CommercialShippingRepositoryInterface $commercialShippingRepository)
	{
	}

	/**
	 * @return array{
	 *     used_ships: int,
	 *     incoming: list<list<CommercialShipping>>,
	 *     outgoing: list<list<CommercialShipping>>,
	 * }
	 */
	public function __invoke(Planet $planet)
	{
		$commercialShippingsData = [
			'used_ships' => 0,
			'incoming' => [
				CommercialShipping::ST_WAITING => [],
				CommercialShipping::ST_GOING => [],
				CommercialShipping::ST_MOVING_BACK => [],
			],
			'outgoing' => [
				CommercialShipping::ST_WAITING => [],
				CommercialShipping::ST_GOING => [],
				CommercialShipping::ST_MOVING_BACK => [],
			],
		];
		$commercialShippings = $this->commercialShippingRepository->getByPlanet($planet);

		foreach ($commercialShippings as $commercialShipping) {
			if ($commercialShipping->originBase->id->equals($planet->id)) {
				$commercialShippingsData['used_ships'] += $commercialShipping->shipQuantity;
				$commercialShippingsData['outgoing'][$commercialShipping->statement][] = $commercialShipping;
			}
			if ($commercialShipping->destinationBase?->id->equals($planet->id)) {
				$commercialShippingsData['incoming'][$commercialShipping->statement][] = $commercialShipping;
			}
		}

		return $commercialShippingsData;
	}
}
