<?php

namespace App\Modules\Gaia\Domain\Entity;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Gaia\Resource\PlanetResource;
use App\Modules\Shared\Domain\Model\SystemUpdatable;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'galaxy__planets')]
class Planet implements SystemUpdatable, \JsonSerializable
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
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne(targetEntity: Place::class, inversedBy: 'base')]
		public Place $place,
		#[ORM\ManyToOne(targetEntity: Player::class)]
		#[ORM\JoinColumn(nullable: true)]
		public Player|null $player,
		#[ORM\Column(type: 'string', length: 45)]
		public string $name,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $typeOfBase = 0,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $levelGenerator = 1,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $levelRefinery = 1,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $levelDock1 = 1,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $levelDock2 = 0,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $levelDock3 = 0,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $levelTechnosphere = 1,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $levelCommercialPlateforme = 0,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $levelStorage = 1,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $levelRecycling = 0,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $levelSpatioport = 0,
		#[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
		public int $points = 0,
		#[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
		public int $iSchool = 1000,
		#[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
		public int $iAntiSpy = 0,
		#[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
		public int $antiSpyAverage = 0,
		#[ORM\Column(type: 'json')]
		public array $shipStorage = [],
		#[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
		public int $resourcesStorage = 5000,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
		#[ORM\Column(type: 'datetime_immutable')]
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
			PlanetResource::GENERATOR => $this->levelGenerator,
			PlanetResource::REFINERY => $this->levelRefinery,
			PlanetResource::DOCK1 => $this->levelDock1,
			PlanetResource::DOCK2 => $this->levelDock2,
			PlanetResource::DOCK3 => $this->levelDock3,
			PlanetResource::TECHNOSPHERE => $this->levelTechnosphere,
			PlanetResource::COMMERCIAL_PLATEFORME => $this->levelCommercialPlateforme,
			PlanetResource::STORAGE => $this->levelStorage,
			PlanetResource::RECYCLING => $this->levelRecycling,
			PlanetResource::SPATIOPORT => $this->levelSpatioport,
		};
	}

	public function setBuildingLevel(int $key, int $level): static
	{
		match ($key) {
			PlanetResource::GENERATOR => $this->levelGenerator = $level,
			PlanetResource::REFINERY => $this->levelRefinery = $level,
			PlanetResource::DOCK1 => $this->levelDock1 = $level,
			PlanetResource::DOCK2 => $this->levelDock2 = $level,
			PlanetResource::DOCK3 => $this->levelDock3 = $level,
			PlanetResource::TECHNOSPHERE => $this->levelTechnosphere = $level,
			PlanetResource::COMMERCIAL_PLATEFORME => $this->levelCommercialPlateforme = $level,
			PlanetResource::STORAGE => $this->levelStorage = $level,
			PlanetResource::RECYCLING => $this->levelRecycling = $level,
			PlanetResource::SPATIOPORT => $this->levelSpatioport = $level,
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
