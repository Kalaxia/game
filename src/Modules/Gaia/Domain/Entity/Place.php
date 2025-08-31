<?php

namespace App\Modules\Gaia\Domain\Entity;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Domain\Enum\PlaceType;
use App\Modules\Shared\Domain\Model\SystemUpdatable;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'galaxy__places')]
class Place implements SystemUpdatable
{
	public const COEFFMAXRESOURCE = 600;
	public const COEFFRESOURCE = 2;
	public const REPOPDANGER = 2;
	public const COEFFPOPRESOURCE = 50;
	public const COEFFDANGER = 5;

	// typeOfPlace
	public const TERRESTRIAL = 1;
	public const EMPTYZONE = 6; // zone vide

	// CONST PNJ COMMANDER
	public const LEVELMAXVCOMMANDER = 20;
	public const POPMAX = 250;
	public const DANGERMAX = 100;

	// CONST RESULT BATTLE
	public const CHANGESUCCESS = 10;
	public const CHANGEFAIL = 11;
	public const CHANGELOST = 12;

	public const LOOTEMPTYSSUCCESS = 20;
	public const LOOTEMPTYFAIL = 21;
	public const LOOTPLAYERWHITBATTLESUCCESS = 22;
	public const LOOTPLAYERWHITBATTLEFAIL = 23;
	public const LOOTPLAYERWHITOUTBATTLESUCCESS = 24;
	public const LOOTLOST = 27;

	public const CONQUEREMPTYSSUCCESS = 30;
	public const CONQUEREMPTYFAIL = 31;
	public const CONQUERPLAYERWHITBATTLESUCCESS = 32;
	public const CONQUERPLAYERWHITBATTLEFAIL = 33;
	public const CONQUERPLAYERWHITOUTBATTLESUCCESS = 34;
	public const CONQUERLOST = 37;

	public const COMEBACK = 40;

	// constante de danger
	public const DNG_CASUAL = 10;
	public const DNG_EASY = 20;
	public const DNG_MEDIUM = 50;
	public const DNG_HARD = 75;
	public const DNG_VERY_HARD = 100;

	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne(targetEntity: OrbitalBase::class, inversedBy: 'place')]
		#[ORM\JoinColumn(nullable: true)]
		public OrbitalBase|null $base,
		#[ORM\ManyToOne(targetEntity: System::class)]
		public System $system,
		#[ORM\Column(type: 'smallint', enumType: PlaceType::class, options: ['unsigned' => true])]
		public PlaceType $typeOfPlace,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $position,
		#[ORM\Column(type: 'float', options: ['unsigned' => true])]
		public float $population,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $coefResources,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $coefHistory,
		#[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
		public int $resources, 						// de la place si $typeOfBase = 0, sinon de la base
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $danger,							// danger actuel de la place (force des flottes rebelles)
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
		public int $maxDanger,						// danger max de la place (force des flottes rebelles)
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $updatedAt,
	) {
			
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
}
