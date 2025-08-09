<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Ship;

use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Athena\Model\ShipQueue;

readonly class CountQueuedShipPoints
{
	public function __construct(
		private GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	) {
	}

	/**
	 * @param list<ShipQueue> $shipQueues
	 */
	public function __invoke(array $shipQueues): int
	{
		$inQueue = 0;

		foreach ($shipQueues as $shipQueue) {
			$inQueue += ($this->getShipCategoriesConfiguration)($shipQueue->shipNumber, 'pev') * $shipQueue->quantity;
		}

		return $inQueue;
	}
}
