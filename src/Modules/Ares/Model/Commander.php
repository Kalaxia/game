<?php

declare(strict_types=1);

namespace App\Modules\Ares\Model;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Ares\Domain\Model\Battle\OfficerTrait;
use App\Modules\Ares\Domain\Model\CommanderInterface;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Model\SystemUpdatable;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Model\TravellerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

class Commander implements CommanderInterface, TravellerInterface, \JsonSerializable, SystemUpdatable
{
	/** Battle the fleet takes part in, while its statement is IN_BATTLE */
	public ?Battle $battle = null;
	/** Experience earned in the last battle, for notifications (not stored) */
	public int $earnedExperience = 0;

	// variables de déplacement
	public ?\DateTimeImmutable $departedAt = null;
	public ?\DateTimeImmutable $arrivedAt = null;
	public int $resources = 0;
	public ?CommanderMission $travelType = null;
	public ?Planet $startPlace = null;
	public ?Planet $destinationPlace = null;
	/** @var list<string> officer traits (OfficerTrait values), drawn as the officer levels up (D20) */
	public array $traits = [];

	public function __construct(
		public Uuid $id,
		public string $name,
		public string $avatar,
		public ?Player $player,
		public ?Planet $base,
		public \DateTimeImmutable $enlistedAt,
		public int $experience = 0,
		public int $sexe = 0,
		public int $age = 0,
		public int $level = 0,
		public int $uExperience = 0,
		public int $palmares = 0,
		public int $statement = Commander::INSCHOOL,
		public ?string $comment = null,
		/** @var Collection<Squadron> */
		public Collection $squadrons = new ArrayCollection(),
		public ?\DateTimeImmutable $assignedAt = null,
		public ?\DateTimeImmutable $diedAt = null,
		public ?\DateTimeImmutable $updatedAt = null,
	) {
	}

	public const COEFFSCHOOL = 100;
	public const COEFFEARNEDEXP = 50;
	public const COEFFEXPPLAYER = 100;
	public const CMDBASELVL = 100;

	public const FLEETSPEED = 35;

	public const COEFFMOVEINSYSTEM = 584;
	public const COEFFMOVEOUTOFSYSTEM = 600;
	public const COEFFMOVEINTERSYSTEM = 50000;

	public const LVLINCOMECOMMANDER = 200;

	public const CREDITCOEFFTOCOLONIZE = 80000;
	public const CREDITCOEFFTOCONQUER = 150000;

	// loot const
	public const LIMITTOLOOT = 5000;
	public const COEFFLOOT = 275;

	// Commander statements
	public const INSCHOOL = 0; // dans l'école
	public const AFFECTED = 1; // autour de la base
	public const MOVING = 2; // en déplacement
	public const DEAD = 3; // mort
	public const DESERT = 4; // déserté
	public const RETIRED = 5; // à la retraite
	public const ONSALE = 6; // dans le marché
	public const RESERVE = 7; // dans la réserve (comme à l'école mais n'apprend pas)
	public const IN_BATTLE = 8; // engagé dans une bataille, en orbite d'une planète

	// types of travel
	public const MOVE = 0; // déplacement
	public const LOOT = 1; // pillage
	public const COLO = 2; // colo ou conquete
	public const BACK = 3; // retour après une action

	public const MAXTRAVELTIME = 57600;
	public const DISTANCEMAX = 30;

	public function isMoving(): bool
	{
		return self::MOVING === $this->statement;
	}

	public function isAffected(): bool
	{
		return self::AFFECTED === $this->statement;
	}

	public function isInSchool(): bool
	{
		return self::INSCHOOL === $this->statement;
	}

	public function isInReserve(): bool
	{
		return self::RESERVE === $this->statement;
	}

	public function isInBattle(): bool
	{
		return self::IN_BATTLE === $this->statement;
	}

	public function isDead(): bool
	{
		return self::DEAD === $this->statement;
	}

	public function isOnSale(): bool
	{
		return self::ONSALE === $this->statement;
	}

	public function isTransferring(): bool
	{
		return CommanderMission::Move === $this->travelType;
	}

	public function isLooting(): bool
	{
		return CommanderMission::Loot === $this->travelType;
	}

	public function isInvading(): bool
	{
		return CommanderMission::Colo === $this->travelType;
	}

	public function isComingBack(): bool
	{
		return CommanderMission::Back === $this->travelType;
	}

	public function isVictorious(): bool
	{
		return self::DEAD !== $this->statement;
	}

	public function getSquadronAt(int $q, int $r): ?Squadron
	{
		return $this->squadrons->findFirst(fn (int $key, Squadron $squadron) => $squadron->isAt($q, $r));
	}

	public function hasShips(): bool
	{
		return $this->squadrons->exists(fn (int $key, Squadron $squadron) => $squadron->ships > 0);
	}

	/**
	 * @return array<int, int> number of ships by ship category value
	 */
	public function getNbrShipByType(): array
	{
		$ships = array_fill(0, count(ShipCategory::cases()), 0);

		foreach ($this->squadrons as $squadron) {
			$ships[$squadron->shipCategory->value] += $squadron->ships;
		}

		return $ships;
	}

	/**
	 * @return list<OfficerTrait>
	 */
	public function getTraits(): array
	{
		return array_values(array_filter(array_map(OfficerTrait::tryFrom(...), $this->traits)));
	}

	public function getDepartureDate(): ?\DateTimeImmutable
	{
		return $this->departedAt;
	}

	public function getArrivalDate(): ?\DateTimeImmutable
	{
		return $this->arrivedAt;
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'avatar' => $this->avatar,
		];
	}

	public function lastUpdatedBySystemAt(): \DateTimeImmutable
	{
		return $this->updatedAt;
	}

	public function isVirtual(): bool
	{
		return null === $this->player;
	}
}
