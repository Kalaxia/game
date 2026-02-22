<?php

namespace App\Modules\Ares\Model;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;

class LiveReport
{
	public static array $squadrons = [];
	public static int $halfRound = 0;
	public static int $littleRound = 0;

	public static ?Player $rPlayerAttacker = null;
	public static ?Player $rPlayerDefender = null;
	public static ?Player $rPlayerWinner = null;
	public static ?Commander $attackerCommander = null;
	public static ?Commander $defenderCommander = null;
	public static ?Planet $rPlace = null;
	public static int $levelA = 0;
	public static int $levelD = 0;
	public static int $experienceA = 0;
	public static int $experienceD = 0;
	public static int $palmaresA = 0;
	public static int $palmaresD = 0;
	public static int $resources = 0;
	public static int $expCom = 0;
	public static int $expPlayerA = 0;
	public static int $expPlayerD = 0;
	public static int $type = 0;
	public static bool $isLegal;
	public static int $round = 0;
	public static int $attackerImportance = 0;
	public static int $defenderImportance = 0;
	public static int $statementAttacker = 0;
	public static int $statementDefender = 0;
	public static ?\DateTimeImmutable $dFight = null;

	public static function clear(): void
	{
		self::$squadrons = [];
		self::$halfRound = 0;
		self::$littleRound = 0;

		self::$rPlayerAttacker = null;
		self::$rPlayerDefender = null;
		self::$rPlayerWinner = null;
		self::$attackerCommander = null;
		self::$defenderCommander = null;
		self::$rPlace = null;
		self::$levelA = 0;
		self::$levelD = 0;
		self::$experienceA = 0;
		self::$experienceD = 0;
		self::$palmaresA = 0;
		self::$palmaresD = 0;
		self::$resources = 0;
		self::$expCom = 0;
		self::$expPlayerA = 0;
		self::$expPlayerD = 0;
		self::$type = 0;
		self::$isLegal = false;
		self::$round = 0;
		self::$attackerImportance = 0;
		self::$defenderImportance = 0;
		self::$statementAttacker = 0;
		self::$statementDefender = 0;
		self::$dFight = null;
	}
}
