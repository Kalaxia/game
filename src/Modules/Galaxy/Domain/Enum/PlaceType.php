<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Enum;

use App\Modules\Galaxy\Domain\Entity\Asteroid;
use App\Modules\Galaxy\Domain\Entity\EmptyPlace;
use App\Modules\Galaxy\Domain\Entity\GasPlanet;
use App\Modules\Galaxy\Domain\Entity\GasPocket;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Entity\Ruin;

enum PlaceType: int
{
	case Planet = 1;
	case GasPlanet = 2;
	case Ruin = 3;
	case GasPocket = 4;
	case Asteroid = 5;
	case Empty = 6;

	/**
	 * @return array{
	 *     resources: int,
	 *     credits: int,
	 *     history: int,
	 * }
	 */
	public function getCoefficients(): array
	{
		return match ($this) {
			self::Planet => [
				'resources' => 0,
				'credits' => 0,
				'history' => 0,
			],
			self::GasPlanet => [
				'resources' => 38,
				'credits' => 52,
				'history' => 10,
			],
			self::Ruin => [
				'resources' => 5,
				'credits' => 0,
				'history' => 95,
			],
			self::GasPocket => [
				'resources' => 0,
				'credits' => 96,
				'history' => 4,
			],
			self::Asteroid => [
				'resources' => 98,
				'credits' => 0,
				'history' => 2,
			],
			self::Empty => [
				'resources' => 0,
				'credits' => 0,
				'history' => 0,
			],
		};
	}

	public function getClassName(): string
	{
		return match ($this) {
			self::Planet => Planet::class,
			self::GasPlanet => GasPlanet::class,
			self::Ruin => Ruin::class,
			self::GasPocket => GasPocket::class,
			self::Asteroid => Asteroid::class,
			self::Empty => EmptyPlace::class,
		};
	}
}
