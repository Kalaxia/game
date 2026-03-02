<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Galaxy;

class GalaxyConfigurationTestEnvironment extends GalaxyConfiguration
{
	public array $galaxy = [
		'size' => 10,
		'diag' => 14,
		'mask' => 0,
		'systemProportion' => [20, 30, 50],
		'systemPosition' => null,
		'population' => [0, 100, 200, 300, 400, 500, 600, 700, 800, 900],
	];

	public array $sectors = [
		[
			'id' => 1,
			'beginColor' => 1,
			'vertices' => [0, 40, 10, 30, 30, 50, 30, 70, 0, 70],
			'barycentre' => [14, 52],
			'display' => [14, 52],
			'name' => 'Sector A',
			'danger' => self::DNG_CASUAL,
			'points' => 1,
		],
		[
			'id' => 2,
			'beginColor' => 2,
			'vertices' => [10, 30, 30, 10, 60, 30, 30, 30, 40, 50, 30, 50],
			'barycentre' => [33, 25],
			'display' => [33, 25],
			'name' => 'Sector B',
			'danger' => self::DNG_EASY,
			'points' => 2,
		],
		[
			'id' => 3,
			'beginColor' => null,
			'vertices' => [30, 10, 40, 0, 90, 0, 70, 20, 60, 10, 60, 30],
			'barycentre' => [50, 12],
			'display' => [50, 12],
			'name' => 'Sector C',
			'danger' => self::DNG_MEDIUM,
			'points' => 3,
		],
	];
}
