<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enum;

enum FeatureFlag: string
{
	case PlanetActivities = 'planet_activities';
	case PlanetBuildings = 'planet_buildings';
}
