<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Domain\Enum;

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
}
