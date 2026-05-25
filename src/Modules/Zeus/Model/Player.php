<?php

namespace App\Modules\Zeus\Model;

use App\Modules\Demeter\Model\Color;
use App\Modules\Portal\Domain\Entity\User;
use App\Modules\Shared\Domain\Model\SystemUpdatable;
use App\Modules\Zeus\Domain\Enum\PlayerStatement;
use App\Modules\Zeus\Domain\Enum\PlayerStatus;
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
	public PlayerStatus $status = PlayerStatus::Standard;
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
	public PlayerStatement $statement = PlayerStatement::Active;
	public ?\DateTimeImmutable $lastReadNewsAt = null;

	public bool $synchronized = false;

	public function isInGame(): bool
	{
		return in_array($this->statement, [
			PlayerStatement::Active,
			PlayerStatement::Inactive,
			PlayerStatement::Holiday,
			PlayerStatement::Banned,
		]);
	}

	public function isAlive(): bool
	{
		return PlayerStatement::Dead !== $this->statement;
	}

	// @TODO transform into Voter
	public function canAccess(): bool
	{
		return in_array($this->statement, [
			PlayerStatement::Active,
			PlayerStatement::Inactive,
			PlayerStatement::Holiday,
		]);
	}

	public function isSynchronized(): bool
	{
		return $this->synchronized;
	}

	public function isRuler(): bool
	{
		return PlayerStatus::Chief === $this->status;
	}

	public function isSenator(): bool
	{
		return PlayerStatus::Parliament === $this->status;
	}

	public function isGovernmentMember(): bool
	{
		return in_array($this->status, [
			PlayerStatus::Chief,
			PlayerStatus::Warlord,
			PlayerStatus::Treasurer,
			PlayerStatus::Minister,
		]);
	}

	public function isTreasurer(): bool
	{
		return PlayerStatus::Treasurer === $this->status;
	}

	public function isParliamentMember(): bool
	{
		return $this->isSenator() || $this->isGovernmentMember();
	}

	public function isPeopleMember(): bool
	{
		return PlayerStatus::Standard === $this->status;
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

	public function getGender(): string
	{
		return 'male';
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
