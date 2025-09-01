<?php

/**
 * ShipQueue.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 10.02.14
 */

namespace App\Modules\Athena\Model;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Shared\Domain\Model\QueueableInterface;
use Symfony\Component\Uid\Uuid;

class ShipQueue implements QueueableInterface
{
	public function __construct(
		public Uuid                    $id,
		public Planet                  $base,
		public \DateTimeImmutable      $startedAt,
		public \DateTimeImmutable|null $endedAt = null,
		public int                     $dockType = 0,
		public int                     $shipNumber = 0,
		public int                     $quantity = 1,
	) {
	}

	public function getStartDate(): \DateTimeImmutable
	{
		return $this->startedAt;
	}

	public function getEndDate(): \DateTimeImmutable
	{
		return $this->endedAt ?? throw new \RuntimeException('Ending date cannot be null');
	}

	public function getResourceIdentifier(): int
	{
		return $this->shipNumber;
	}
}
