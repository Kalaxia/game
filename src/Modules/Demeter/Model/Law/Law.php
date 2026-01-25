<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Model\Law;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'law')]
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
		#[ORM\Id]
		#[ORM\Column(type: 'uuid')]
		public Uuid $id,
		#[ORM\ManyToOne]
		public Color $faction,
		#[ORM\Column(type: 'smallint')]
		public int $type,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $voteEndedAt,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $endedAt,
		#[ORM\Column(type: 'datetime_immutable')]
		public \DateTimeImmutable $createdAt,
		#[ORM\Column(type: 'json')]
		public array $options = [],
		#[ORM\Column(type: 'smallint')]
		public int $statement = 0,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
		public int $forVote = 0,
		#[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
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
