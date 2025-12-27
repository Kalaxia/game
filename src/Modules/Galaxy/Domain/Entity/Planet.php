<?php

namespace App\Modules\Galaxy\Domain\Entity;

use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Galaxy\Domain\Enum\PlaceType;
use App\Modules\Galaxy\Domain\Enum\PlanetType;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Shared\Domain\Model\SystemUpdatable;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'galaxy__planets')]
class Planet extends Place implements SystemUpdatable, \JsonSerializable
{
	// type of base
	public const BASE_TYPE_COLONY = 0;
	public const BASE_TYPE_COMMERCIAL = 1;
	public const BASE_TYPE_MILITARY = 2;
	public const BASE_TYPE_CAPITAL = 3;

	public const MAXCOMMANDERSTANDARD = 2;
	public const MAXCOMMANDERMILITARY = 5;
	public const EXTRA_STOCK = 25000;

	public const MAXCOMMANDERINMESS = 20;

	public const DOCK_TYPE_MANUFACTURE = 'manufacture';
	public const DOCK_TYPE_SHIPYARD = 'shipyard';

	public function __construct(
		public Uuid $id,
		public System      $system,
		public int         $position,
		#[ORM\Column(type: 'string', enumType: PlanetType::class, length: 32)]
		public PlanetType $planetType,
		#[ORM\ManyToOne(targetEntity: Player::class)]
		#[ORM\JoinColumn(nullable: true)]
		public Player|null $player = null,
		#[ORM\Column(type: 'string', length: 45, nullable: true)]
		public string|null $name = null,
		#[ORM\Column(type: 'float', options: ['unsigned' => true])]
		public float       $population = 0.0,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int         $coefResources = 0,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $coefHistory = 0,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $danger = 0,							// danger actuel de la place (force des flottes rebelles)
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $maxDanger = 0,						// danger max de la place (force des flottes rebelles)
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
		public \DateTimeImmutable $updatedAt = new \DateTimeImmutable(),
	) {
		parent::__construct($id, $system, $position, $updatedAt);
	}

	public function getType(): PlaceType
	{
		return PlaceType::Planet;
	}

	public function getShipStorage(): array
	{
		return $this->shipStorage + array_fill(0, 12, 0);
	}

	public function isCapital(): bool
	{
		return self::BASE_TYPE_CAPITAL === $this->typeOfBase;
	}

	public function isMilitaryBase(): bool
	{
		return self::BASE_TYPE_MILITARY === $this->typeOfBase;
	}

	public function isCommercialBase(): bool
	{
		return self::BASE_TYPE_COMMERCIAL === $this->typeOfBase;
	}

	public function isColony(): bool
	{
		return self::BASE_TYPE_COLONY === $this->typeOfBase;
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

	public function getMaxResources(): int
	{
		return intval(
			ceil($this->population / static::COEFFPOPRESOURCE)
			* static::COEFFMAXRESOURCE
			* ($this->maxDanger + 1)
		);
	}

	public function getProducedResources(): int
	{
		return intval(floor(static::COEFFRESOURCE * $this->population));
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
