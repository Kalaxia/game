<?php

namespace App\Modules\Zeus\Model;

use App\Modules\Demeter\Model\Color;
use App\Modules\Portal\Domain\Entity\User;
use App\Modules\Shared\Domain\Model\SystemUpdatable;
use App\Modules\Zeus\Resource\TutorialResource;

class Player implements CreditHolderInterface, SystemUpdatable, \JsonSerializable
{
	public ?int $id = 0;
	public ?User $user = null;
	public ?Color $faction = null;
	public ?Player $godFather = null;
	public string $name = '';
	public int $sex = 0;
	public string $description = '';
	public string $avatar = '';
	public int $status = 1;
	// @TODO rename to credits
	public int $credit = 0;
	public int $experience = 0;
	public int $factionPoint = 0;
	public int $level = 0;
	public int $victory = 0;
	public int $defeat = 0;
	public int $stepTutorial = 1;
	public bool $stepDone = false;
	public int $iUniversity = 5000;
	public int $partNaturalSciences = 25;
	public int $partLifeSciences = 25;
	public int $partSocialPoliticalSciences = 25;
	public int $partInformaticEngineering = 25;
	public ?\DateTimeImmutable $uPlayer = null;
	public ?\DateTimeImmutable $dInscription = null;
	public ?\DateTimeImmutable $dLastConnection = null;
	public ?\DateTimeImmutable $dLastActivity = null;
	public bool $premium = false;
	public int $statement = 0;

	public bool $synchronized = false;

	public const ACTIVE = 1;
	public const INACTIVE = 2;
	public const HOLIDAY = 3;
	public const BANNED = 4;
	public const DELETED = 5;
	public const DEAD = 6;

	public const STANDARD = 1;
	public const PARLIAMENT = 2;
	public const TREASURER = 3;
	public const WARLORD = 4;
	public const MINISTER = 5;
	public const CHIEF = 6;

	public function isInGame(): bool
	{
		return in_array($this->statement, [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY, Player::BANNED]);
	}

	public function isAlive(): bool
	{
		return static::DEAD !== $this->statement;
	}

	// @TODO transform into Voter
	public function canAccess(): bool
	{
		return in_array($this->statement, [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]);
	}

	public function isSynchronized(): bool
	{
		return $this->synchronized;
	}

	public function isRuler(): bool
	{
		return self::CHIEF === $this->status;
	}

	public function isSenator(): bool
	{
		return self::PARLIAMENT === $this->status;
	}

	public function isGovernmentMember(): bool
	{
		return in_array($this->status, [self::CHIEF, self::WARLORD, self::TREASURER, self::MINISTER]);
	}

	public function isTreasurer(): bool
	{
		return self::TREASURER === $this->status;
	}

	public function isParliamentMember(): bool
	{
		return $this->isSenator() || $this->isGovernmentMember();
	}

	public function isPeopleMember(): bool
	{
		return self::STANDARD === $this->status;
	}

	public function setCredits(int $credit): static
	{
		$this->credit = $credit;

		return $this;
	}

	public function getCredits(): int
	{
		return $this->credit;
	}

	public function canAfford(int $amount): bool
	{
		return $this->credit >= $amount;
	}

	public function lastUpdatedBySystemAt(): \DateTimeImmutable
	{
		return $this->uPlayer;
	}

	public function hasCompletedTutorial(): bool
	{
		return !TutorialResource::stepExists($this->stepTutorial);
	}

	public function getRoles(): array
	{
		return ['ROLE_USER'];
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'avatar' => $this->avatar,
		];
	}
}
