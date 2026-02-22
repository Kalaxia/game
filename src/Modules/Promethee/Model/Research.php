<?php

namespace App\Modules\Promethee\Model;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class Research
{
	public const DOMAIN_NATURAL_SCIENCES = 'natural_sciences';
	public const DOMAIN_LIFE_SCIENCES = 'life_sciences';
	public const DOMAIN_SOCIAL_POLITICAL_SCIENCES = 'social_political_sciences';
	public const DOMAIN_INFORMATIC_ENGINEERING = 'informatic_engineering';

	public function __construct(
		public Uuid $id,
		public Player $player,
		public int $naturalToPay,
		public int $lifeToPay,
		public int $socialToPay,
		public int $informaticToPay,
		public int $mathLevel = 0,						// naturalTech
		public int $physLevel = 0,
		public int $chemLevel = 0,
		public int $bioLevel = 0,	// bio == law		//lifeTech
		public int $mediLevel = 0,  // medi == comm
		public int $econoLevel = 0,						// socialTech
		public int $psychoLevel = 0,
		public int $networkLevel = 0,					// informaticTech
		public int $algoLevel = 0,
		public int $statLevel = 0,
		public int $naturalTech = 0,
		public int $lifeTech = 0,
		public int $socialTech = 0,
		public int $informaticTech = 0,
	) {
	}

	public const MATH = 0;
	public const PHYS = 1;
	public const CHEM = 2;
	public const LAW = 3;
	public const COMM = 4;
	public const ECONO = 5;
	public const PSYCHO = 6;
	public const NETWORK = 7;
	public const ALGO = 8;
	public const STAT = 9;

	public function getLevel(int $id): int
	{
		return match ($id) {
			0 => $this->mathLevel,
			1 => $this->physLevel,
			2 => $this->chemLevel,
			3 => $this->bioLevel,
			4 => $this->mediLevel,
			5 => $this->econoLevel,
			6 => $this->psychoLevel,
			7 => $this->networkLevel,
			8 => $this->algoLevel,
			9 => $this->statLevel,
			default => false,
		};
	}
}
