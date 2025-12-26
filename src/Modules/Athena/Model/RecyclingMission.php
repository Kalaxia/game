<?php

/**
 * RecyclingMission.
 *
 * @author Jacky Casas
 * @copyright Asylamba
 *
 * @update 09.02.15
 */

namespace App\Modules\Athena\Model;

use App\Modules\Galaxy\Domain\Entity\Place;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Entity\UninhabitedPlace;
use Symfony\Component\Uid\Uuid;

class RecyclingMission
{
	public const ST_DELETED = 0;
	public const ST_ACTIVE = 1;
	public const ST_BEING_DELETED = 2;

	public const RECYCLER_CAPACTIY = 400;
	public const RECYCLING_TIME = 28800; // 8 hours, in seconds
	public const COEF_SHIP = 1.6; // to convert points to resource for ships
		// coef_ship a été calculé par un ingénieur. Si on change la capacité, il faut rechanger coef_ship

	public function __construct(
		public Uuid                    $id,
		public Planet                  $base,
		public UninhabitedPlace        $target,
		public int                     $cycleTime = 0,
		public int                     $recyclerQuantity = 0,
		public int                     $addToNextMission = 0,
		public int                     $statement = self::ST_ACTIVE,
		public \DateTimeImmutable|null $endedAt = null,
	) {
	}

	public function cancel(): void
	{
		$this->statement = static::ST_BEING_DELETED;
	}

	public function stop(): void
	{
		$this->statement = static::ST_DELETED;
	}

	public function isActive(): bool
	{
		return static::ST_ACTIVE === $this->statement;
	}

	public function isBeingDeleted(): bool
	{
		return static::ST_BEING_DELETED === $this->statement;
	}

	public function isDeleted(): bool
	{
		return static::ST_DELETED === $this->statement;
	}
}
