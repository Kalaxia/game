<?php

namespace App\Modules\Athena\Model;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Gaia\Model\Place;
use App\Modules\Shared\Domain\Model\SystemUpdatable;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class OrbitalBase implements SystemUpdatable, \JsonSerializable
{
	// type of base
	public const TYP_NEUTRAL = 0;
	public const TYP_COMMERCIAL = 1;
	public const TYP_MILITARY = 2;
	public const TYP_CAPITAL = 3;

	public const MAXCOMMANDERSTANDARD = 2;
	public const MAXCOMMANDERMILITARY = 5;
	public const EXTRA_STOCK = 25000;

	public const MAXCOMMANDERINMESS = 20;

	public const DOCK_TYPE_MANUFACTURE = 'manufacture';
	public const DOCK_TYPE_SHIPYARD = 'shipyard';

	public function __construct(
		public Uuid $id,
		public Place $place,
		public Player|null $player,
		public string $name,
		public int $typeOfBase = 0,
		public int $levelGenerator = 1,
		public int $levelRefinery = 1,
		public int $levelDock1 = 1,
		public int $levelDock2 = 0,
		public int $levelDock3 = 0,
		public int $levelTechnosphere = 1,
		public int $levelCommercialPlateforme = 0,
		public int $levelStorage = 1,
		public int $levelRecycling = 0,
		public int $levelSpatioport = 0,
		public int $points = 0,
		public int $iSchool = 1000,
		public int $iAntiSpy = 0,
		public int $antiSpyAverage = 0,
		public array $shipStorage = [],
		public int $resourcesStorage = 5000,
		public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
		public \DateTimeImmutable $updatedAt = new \DateTimeImmutable(),
	) {
	}

	public function getShipStorage(): array
	{
		return $this->shipStorage + array_fill(0, 12, 0);
	}

	public function isCapital(): bool
	{
		return self::TYP_CAPITAL === $this->typeOfBase;
	}

	public function isMilitaryBase(): bool
	{
		return self::TYP_MILITARY === $this->typeOfBase;
	}

	public function isCommercialBase(): bool
	{
		return self::TYP_COMMERCIAL === $this->typeOfBase;
	}

	public function isColony(): bool
	{
		return self::TYP_NEUTRAL === $this->typeOfBase;
	}

	public function addShips(int|ShipCategory $shipCategory, int $quantity): void
	{
		if ($shipCategory instanceof ShipCategory) {
			$shipIdentifier = $shipCategory->value;
		} else {
			trigger_error(
				sprintf('Calling method %s with an integer as first argument is deprecated. Please use ShipCategory enum instead.', __METHOD__),
				E_USER_DEPRECATED,
			);

			$shipIdentifier = $shipCategory;
		}

		$currentQuantity = $this->shipStorage[$shipIdentifier] ?? 0;

		$this->shipStorage[$shipIdentifier] = $currentQuantity + $quantity;
	}

	public function removeShips(int $shipIdentifier, int $quantity): void
	{
		$currentQuantity = $this->shipStorage[$shipIdentifier] ?? 0;

		$this->shipStorage[$shipIdentifier] = $currentQuantity - $quantity;

		if ($this->shipStorage[$shipIdentifier] <= 0) {
			$this->shipStorage[$shipIdentifier] = 0;
		}
	}

	public function getBuildingLevel(int $key): int
	{
		return match ($key) {
			OrbitalBaseResource::GENERATOR => $this->levelGenerator,
			OrbitalBaseResource::REFINERY => $this->levelRefinery,
			OrbitalBaseResource::DOCK1 => $this->levelDock1,
			OrbitalBaseResource::DOCK2 => $this->levelDock2,
			OrbitalBaseResource::DOCK3 => $this->levelDock3,
			OrbitalBaseResource::TECHNOSPHERE => $this->levelTechnosphere,
			OrbitalBaseResource::COMMERCIAL_PLATEFORME => $this->levelCommercialPlateforme,
			OrbitalBaseResource::STORAGE => $this->levelStorage,
			OrbitalBaseResource::RECYCLING => $this->levelRecycling,
			OrbitalBaseResource::SPATIOPORT => $this->levelSpatioport,
		};
	}

	public function setBuildingLevel(int $key, int $level): static
	{
		match ($key) {
			OrbitalBaseResource::GENERATOR => $this->levelGenerator = $level,
			OrbitalBaseResource::REFINERY => $this->levelRefinery = $level,
			OrbitalBaseResource::DOCK1 => $this->levelDock1 = $level,
			OrbitalBaseResource::DOCK2 => $this->levelDock2 = $level,
			OrbitalBaseResource::DOCK3 => $this->levelDock3 = $level,
			OrbitalBaseResource::TECHNOSPHERE => $this->levelTechnosphere = $level,
			OrbitalBaseResource::COMMERCIAL_PLATEFORME => $this->levelCommercialPlateforme = $level,
			OrbitalBaseResource::STORAGE => $this->levelStorage = $level,
			OrbitalBaseResource::RECYCLING => $this->levelRecycling = $level,
			OrbitalBaseResource::SPATIOPORT => $this->levelSpatioport = $level,
		};

		return $this;
	}

	public function lastUpdatedBySystemAt(): \DateTimeImmutable
	{
		return $this->updatedAt;
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
		];
	}
}
