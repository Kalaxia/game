<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum ResourceType: string
{
	// Minerals
	case Carbon = 'carbon';
	case Cobalt = 'cobalt';
	case Rhenium = 'rhenium';
	case Titanium = 'titanium';
	case Platinum = 'platinum';
	case Crystal = 'crystal';
	case Uranium = 'uranium';
	// Gas
	case Deuterium = 'deuterium';
	case Tritium = 'tritium';
	case Hydrogen = 'hydrogen';
	case Helium = 'helium';
	case Nitrogen = 'nitrogen';

	// Food
	case Cereals = 'cereals';
	case Fruits = 'fruits';
	case Fish = 'fish';
	case Meat = 'meat';

	// Luxury products
	case Coffee = 'coffee';
	case Tea = 'tea';
	case Salt = 'salt';
	case Sugar = 'sugar';
	case Wine = 'wine';
	case Beer = 'beer';
	case Digestive = 'digestive';
}
