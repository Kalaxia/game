<?php

/**
 * ressources pour les commandants.
 *
 * @author Gil Clavien
 * @copyright Expansion - le jeu
 *
 * @update 04.03.2014
 */

namespace App\Modules\Ares\Resource;

class CommanderResources
{
	private static $commanders = [
		[
			'grade' => '3ème Classe',
		],
		[
			'grade' => '2ème Classe',
		],
		[
			'grade' => '1ère Classe',
		],
		[
			'grade' => 'Quartier-Maître',
		],
		[
			'grade' => 'Sergent',
		],
		[
			'grade' => 'Enseigne',
		],
		[
			'grade' => 'Lieutenant',
		],
		[
			'grade' => 'Capitaine',
		],
		[
			'grade' => 'Major',
		],
		[
			'grade' => 'Colonel',
		],
		[
			'grade' => 'Commandant',
		],
		[
			'grade' => 'Commodore',
		],
		[
			'grade' => 'Contre-Amiral',
		],
		[
			'grade' => 'Vice-Amiral',
		],
		[
			'grade' => 'Amiral',
		],
		[
			'grade' => 'Grand Amiral',
		],
		[
			'grade' => 'Grand Amiral',
		],
		[
			'grade' => 'Grand Amiral',
		],
		[
			'grade' => 'Grand Amiral',
		],
		[
			'grade' => 'Grand Amiral',
		],
	];

	public static function getInfo($level, $info)
	{
		if ($level <= self::size()) {
			if (in_array($info, ['grade'])) {
				return self::$commanders[$level - 1][$info];
			}

			return false;
		}

		return false;
	}

	public static function size()
	{
		return count(self::$commanders);
	}
}
