<?php

namespace App\Modules\Ares\Model;

use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Model\ShipCategory;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Model\SystemUpdatable;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Model\TravellerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

class Commander implements TravellerInterface, \JsonSerializable, SystemUpdatable
{
	// variables de combat
	/** @var list<int> */
	public array $squadronsIds = [];
	/** @var list<list<int>> */
	public array $armyAtEnd = [];
	public int $earnedExperience = 0;
	public bool $winner = false;
	public bool|null $isAttacker = null;
	public bool $hasArmySetted = false;

	// variables de déplacement
	public \DateTimeImmutable|null $departedAt = null;
	public \DateTimeImmutable|null $arrivedAt = null;
	public int $resources = 0;
	public CommanderMission|null $travelType = null;
	public Planet|null $startPlace = null;
	public Planet|null $destinationPlace = null;
	// Tableau d'objets squadron
	/**
	 * @var list<Squadron>
	 */
	public array $army = [];

	public function __construct(
        public Uuid               $id,
        public string             $name,
        public string             $avatar,
        public Player|null        $player,
        public Planet|null        $base,
        public \DateTimeImmutable $enlistedAt,
        public int                $experience = 0,
        public int                $sexe = 0,
        public int                $age = 0,
        public int                $level = 0,
        public int                $uExperience = 0,
        public int                $palmares = 0,
        public int                $statement = Commander::INSCHOOL,
        public int                $line = 1,
        public string|null        $comment = null,
        /** @var Collection<Squadron> */
        public Collection $squadrons = new ArrayCollection(),
        public \DateTimeImmutable|null $assignedAt = null,
        public \DateTimeImmutable|null $diedAt = null,
        public \DateTimeImmutable|null $updatedAt = null,
        public bool $hasToU = true,
        public bool $uMethodCtced = false,
        public bool $isVirtual = false
    )
    {
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

	// types of travel
	public const MOVE = 0; // déplacement
	public const LOOT = 1; // pillage
	public const COLO = 2; // colo ou conquete
	public const BACK = 3; // retour après une action

	public const MAXTRAVELTIME = 57600;
	public const DISTANCEMAX = 30;

	// Const de lineCoord
	public static array $LINECOORD = [1, 1, 1, 2, 2, 1, 2, 3, 3, 1, 2, 3, 4, 4, 2, 3, 4, 5, 5, 3, 4, 5, 6, 6, 4, 5, 6, 7, 7, 5, 6, 7];

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

	public function findSquadron(int $position): Squadron|null
	{
		foreach ($this->squadrons as $squadron) {
			if ($squadron->position === $position) {
				return $squadron;
			}
		}

		return null;
	}

	public function getFormatLineCoord(): array
	{
		$return = [];

		for ($i = 0; $i < ($this->level + 1); ++$i) {
			$return[] = self::$LINECOORD[$i];
		}

		return $return;
	}

	public function getSizeArmy(): int
	{
		return count($this->squadronsIds);
	}

	public function getSquadron(int $i): Squadron|null
	{
		if ($i > 16) {
			throw new \LogicException(sprintf('Squadron ID cannot be greater than 16, %d given', $i));
		}

		return $this->squadrons->get($i) ?? $this->army[$i] ?? null;
	}

	/**
	 * @return array<int, int>
	 */
	public function getNbrShipByType(): array
	{
		$array = array_fill(0, count(ShipCategory::cases()), 0);

		foreach ($this->army as $squadron) {
			for ($i = 0; $i < 12; ++$i) {
				$array[$i] += $squadron->getShipQuantity($i);
			}
		}

		return $array;
	}

	public function getDepartureDate(): \DateTimeImmutable|null
	{
		return $this->departedAt;
	}

	public function getArrivalDate(): \DateTimeImmutable|null
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
}
