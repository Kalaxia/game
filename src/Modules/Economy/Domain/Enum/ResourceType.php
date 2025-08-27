<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum ResourceType: string
{
	case Titanium = 'titanium';
	case Crystal = 'crystal';
	case Deuterium = 'deuterium';
	case Tritium = 'tritium';
	case Hydrogen = 'hydrogen';
}
