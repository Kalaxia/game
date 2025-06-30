<?php

namespace App\Modules\Demeter\Model;

use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Zeus\Model\CreditHolderInterface;
use Symfony\Component\Uid\Uuid;

class Color implements CreditHolderInterface
{
	// Regime
	public const REGIME_DEMOCRATIC = 1;
	public const REGIME_ROYALISTIC = 2;
	public const REGIME_THEOCRATIC = 3;

	// Relation avec les autres factions
	public const NEUTRAL = 0;
	public const PEACE = 1;
	public const ALLY = 2;
	public const ENEMY = 3;

	// @TODO Inquire these constants and replace them with configurations
	// constantes de prestiges
	public const TWO_POINTS_PER_LEVEL = 2;
	public const FOUR_POINTS_PER_LEVEL = 4;
	// # empire
	public const POINTCONQUER = 100;
	public const POINTBUILDBIGSHIP = 25;

	// # negore
	public const MIN_PRICE = 10000;
	public const COEF_POINT_SELLING = 0.00002; 	// == * 50K

	// # cardan
	public const BONUSOUTOFSECTOR = 20;
	public const POINTDONATE = 10;
	public const COEFPOINTDONATE = 0.0001;

	// # kovakh
	public const POINTBUILDLITTLESHIP = 1;
	public const POINTCHANGETYPE = 50;
	public const POINT_BATTLE_WIN = 10;
	public const POINT_BATTLE_LOOSE = 20;

	// # Synelle
	public const POINTDEFEND = 20;

	// # Nerve
	public const COEFFPOINTCONQUER = 10;
	// POINTCHANGETYPE aussi

	// # Aphéra
	public const POINTSPY = 10;
	public const POINTRESEARCH = 2;

	// const
	public const NBRGOVERNMENT = 6;

	public const PUTSCHPERCENTAGE = 15;

	public const ALIVE = 1;
	public const DEAD = 0;

	public const MANDATE = 1;
	public const CAMPAIGN = 2;
	public const ELECTION = 3;

	public const NOT_WIN = 0;
	public const WIN = 1;

	/**
	 * @param self::REGIME_* $regime
	 */
	public function __construct(
		public Uuid $id,
		public int $identifier,
		public bool $alive = false,
		public bool $isWinner = false,
		public int $credits = 0,
		public int $rankingPoints = 0,
		public int $points = 0,
		public int $electionStatement = 0,
		public int $regime = self::REGIME_DEMOCRATIC,
		public bool $isClosed = false,
		public string|null $description = null,
		public bool $isInGame = false,
		public array $relations = [],
		// @TODO move that field to the future Server entity
		public \DateTimeImmutable|null $victoryClaimedAt = null,
		// @TODO get that field from the Election table
		public \DateTimeImmutable|null $lastElectionHeldAt = null,
	) {
	}

	public function hasOngoingElectionCampaign(): bool
	{
		return in_array($this->electionStatement, [self::CAMPAIGN, self::ELECTION]);
	}

	public function isInCampaign(): bool
	{
		return self::CAMPAIGN === $this->electionStatement;
	}

	public function isInElection(): bool
	{
		return self::ELECTION === $this->electionStatement;
	}

	public function isInMandate(): bool
	{
		return self::MANDATE === $this->electionStatement;
	}

	public function hasElections(): bool
	{
		return in_array($this->getRegime(), [self::REGIME_DEMOCRATIC, self::REGIME_THEOCRATIC]);
	}

	public function isDemocratic(): bool
	{
		return self::REGIME_DEMOCRATIC === $this->getRegime();
	}

	public function isRoyalistic(): bool
	{
		return self::REGIME_ROYALISTIC === $this->getRegime();
	}

	public function isTheocratic(): bool
	{
		return self::REGIME_THEOCRATIC === $this->getRegime();
	}

	public function getRegime(): int
	{
		return $this->regime;
	}

	public function getCredits(): int
	{
		return $this->credits;
	}

	public function setCredits(int $credits): static
	{
		$this->credits = $credits;

		return $this;
	}

	public function increaseCredit(int $credits): int
	{
		$this->credits += $credits;

		return $this->credits;
	}

	public function decreaseCredit(int $credits): int
	{
		$this->credits -= $credits;

		return $this->credits;
	}

	public function canAfford(int $amount): bool
	{
		return $this->credits >= $amount;
	}

	public function canAttackLegally(Color $faction): bool
	{
		return !in_array($this->relations[$faction->identifier], [static::PEACE, static::ALLY]);
	}
}
