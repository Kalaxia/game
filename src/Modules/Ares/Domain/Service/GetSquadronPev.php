<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service;

use App\Modules\Ares\Model\Squadron;

readonly class GetSquadronPev
{
	public function __construct(private GetShipCategoriesConfiguration $getShipCategoriesConfiguration)
	{
	}

	public function __invoke(Squadron $squadron): int
	{
		return ($this->getShipCategoriesConfiguration)($squadron->shipCategory, 'pev') * $squadron->ships;
	}
}
