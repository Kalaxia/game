<?php

/**
 * loi.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 29.09.14
 */

namespace App\Modules\Demeter\Model\Law;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\Uid\Uuid;

class Law
{
	public const VOTATION = 0;
	public const EFFECTIVE = 1;
	public const OBSOLETE = 2;
	public const REFUSED = 3;

	public const SECTORTAX = 1;
	public const SECTORNAME = 2;
	public const COMTAXEXPORT = 3;
	public const COMTAXIMPORT = 4;
	public const MILITARYSUBVENTION = 5;
	public const TECHNOLOGYTRANSFER = 6;
	public const PEACEPACT = 7;
	public const WARDECLARATION = 8;
	public const TOTALALLIANCE = 9;
	public const NEUTRALPACT = 10;
	public const PUNITION = 11;

	public const VOTEDURATION = 86400;

	public function __construct(
		public Uuid $id,
		public Color $faction,
		public int $type,
		public \DateTimeImmutable $voteEndedAt,
		public \DateTimeImmutable $endedAt,
		public \DateTimeImmutable $createdAt,
		public array $options = [],
		public int $statement = 0,
		public int $forVote = 0,
		public int $againstVote = 0,
	) {
	}

	public function isBeingVoted(): bool
	{
		return self::VOTATION === $this->statement;
	}

	public function isEffective(): bool
	{
		return self::EFFECTIVE === $this->statement;
	}

	public function isObsolete(): bool
	{
		return self::OBSOLETE === $this->statement;
	}

	public function isRefused(): bool
	{
		return self::REFUSED === $this->statement;
	}
}
