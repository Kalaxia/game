<?php

namespace App\Modules\Artemis\Model;

use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Enum\PlaceType;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class SpyReport
{
	// constants
	public const TYP_NOT_CAUGHT = 0;
	public const TYP_ANONYMOUSLY_CAUGHT = 1;
	public const TYP_CAUGHT = 2;

	public const STEP_RESOURCES = 10;
	public const STEP_FLEET = 20;
	public const STEP_ANITSPY = 30;
	public const STEP_COMMANDER = 40;
	public const STEP_RC = 50;
	public const STEP_PEV = 60;
	public const STEP_POINT = 70;
	public const STEP_MOVEMENT = 80;
	public const STEP_ARMY = 95;
	public const STEP_DOCK = 99;

	public function __construct(
		public Uuid $id,
		public Player $player,
		public Planet $place,
		public int $price,
		public ?Color $placeFaction,
		public PlaceType $placeType,
		public ?int $baseType,
		public ?string $placeName,
		public int $points,
		public ?Player $targetPlayer,
		public ?int $targetPlayerLevel,
		public int $resources,
		public array $shipStorage,
		public ?int $antiSpyInvest,
		public ?int $commercialRouteIncome,
		public int $successRate,
		public int $type,
		public \DateTimeImmutable $createdAt,
		public array $commanders = [],
	) {
	}

	public function getShipStorage(): array
	{
		static $storage = null;

		return $storage ??= $this->shipStorage + array_fill(0, 12, 0);
	}

	public function isNotCaught(): bool
	{
		return self::TYP_NOT_CAUGHT === $this->type;
	}

	public function isAnonymouslyCaught(): bool
	{
		return self::TYP_ANONYMOUSLY_CAUGHT === $this->type;
	}

	public function isCaught(): bool
	{
		return self::TYP_CAUGHT === $this->type;
	}

	public function hasSpottedFleets(): bool
	{
		return self::STEP_FLEET < $this->successRate;
	}

	public function hasSpottedArmies(): bool
	{
		return self::STEP_ARMY < $this->successRate;
	}

	public function hasSpottedMovements(): bool
	{
		return self::STEP_MOVEMENT < $this->successRate;
	}

	public function hasSpottedCommanders(): bool
	{
		return self::STEP_COMMANDER < $this->successRate;
	}

	public function hasSpottedPevs(): bool
	{
		return self::STEP_PEV < $this->successRate;
	}

	public function hasSpottedDocks(): bool
	{
		return self::STEP_DOCK < $this->successRate;
	}

	public function hasSpottedResourcesStorage(): bool
	{
		return self::STEP_RESOURCES < $this->successRate;
	}

	public function hasSpottedPoints(): bool
	{
		return self::STEP_POINT < $this->successRate;
	}

	public function hasSpottedAntiSpy(): bool
	{
		return self::STEP_ANITSPY < $this->successRate;
	}

	public function hasSpottedCommercialRoutesIncome(): bool
	{
		return self::STEP_RC < $this->successRate;
	}

	public function hasShipsInStorage(): bool
	{
		return \array_sum($this->shipStorage) > 0;
	}
}
