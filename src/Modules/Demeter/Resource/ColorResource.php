<?php

namespace App\Modules\Demeter\Resource;

use App\Modules\Demeter\Model\Color;

class ColorResource
{
	// constants for factions
	public const NO_FACTION = 0;
	public const EMPIRE = 1;
	public const KOVAHK = 2;
	public const NEGORA = 3;
	public const CARDAN = 4;
	public const NERVE = 5;
	public const APHERA = 6;
	public const SYNELLE = 7;

	public const DEFENSELITTLESHIPBONUS = 0;
	public const PRICEBIGSHIPBONUS = 1;
	public const SPEEDLITTLESHIPBONUS = 2;
	public const DEFENSELITTLESHIPMALUS = 3;
	public const COMMERCIALROUTEINCOMEBONUS = 4;
	public const COMMERCIALROUTEPRICEBONUS = 5;
	public const TAXBONUS = 6;
	public const COLOPRICEBONUS = 7;
	public const LOOTRESOURCESMALUS = 8;
	public const RAFINERYBONUS = 9;
	public const STORAGEBONUS = 10;
	public const BIGACADEMICBONUS = 11;
	public const TECHNOLOGYBONUS = 12;
	public const COMMANDERSCHOOLBONUS = 13;
	public const LITTLEACADEMICBONUS = 14;

	// constants for the actions bonuses of the factions
	public const BONUS_EMPIRE_CRUISER = 5;		// price 5% less for cruiser and heavy cruiser
	public const BONUS_NEGORA_ROUTE = 3;		// price 3% less for the price of a commercial route
	public const BONUS_CARDAN_COLO = 10;		// price 10% less for colo or conquest
	public const BONUS_APHERA_TECHNO = 2;		// 4% less time to build technologies

	public static function getInfo(int $id, string $info): mixed
	{
		if ($id <= count(self::$colors)) {
			if (in_array($info, [
				'id',
				'officialName',
				'popularName',
				'government',
				'demonym',
				'factionPoint',
				'status',
				'regime',
				'devise',
				'desc1',
				'desc2',
				'desc3',
				'desc4',
				'bonus',
				'mandateDuration',
				'senateDesc',
				'campaignDesc', ])) {
				return self::$colors[$id][$info];
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}
