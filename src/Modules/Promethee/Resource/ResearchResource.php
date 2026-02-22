<?php

namespace App\Modules\Promethee\Resource;

class ResearchResource
{
	/**
	 * 0 = math, 1 = physique, 2 = chimie
	 * 3 = biologie (droit), 4 = médecine (communication)
	 * 5 = économie, 6 = psychologie
	 * 7 = réseaux, 8 = algorithmique, 9 = statistiques.
	 **/
	public static $availableResearch = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

	public static $research = [
		[
			'name' => 'Mathématiques',
			'codeName' => 'mathematics',
		],
		[
			'name' => 'Physique',
			'codeName' => 'physics',
		],
		[
			'name' => 'Chimie',
			'codeName' => 'chemistry',
		],
		[
			'name' => 'Droit',
			'codeName' => 'biology',
		],
		[
			'name' => 'Communication',
			'codeName' => 'medicine',
		],
		[
			'name' => 'Economie',
			'codeName' => 'economy',
		],
		[
			'name' => 'Psychologie',
			'codeName' => 'psychology',
		],
		[
			'name' => 'Réseaux',
			'codeName' => 'networks',
		],
		[
			'name' => 'Algorithmique',
			'codeName' => 'algorithmic',
		],
		[
			'name' => 'Statistiques',
			'codeName' => 'statistics',
		],
	];
}
