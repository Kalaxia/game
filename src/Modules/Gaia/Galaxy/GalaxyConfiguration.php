<?php

namespace App\Modules\Gaia\Galaxy;

abstract class GalaxyConfiguration
{
	/**
	 * @var array{
	 *     size: int,
	 *     diag: int,
	 *     mask: int,
	 *     systemProportion: list<int>,
	 *     systemPosition: list<int>|null,
	 *     lineSystemPosition: list<array{
	 *         0: array{0: int, 1: int},
	 *         1: array{0: int, 1: int},
	 *         2: int,
	 *         3: int,
	 *     }>,
	 *     circleSystemPosition: list<array{
	 *          0: array{0: int, 1: int},
	 *          1: int,
	 *          2: int,
	 *          3: int,
	 *      }>,
	 *     population: list<int>,
	 * }
	 */
	public array $galaxy;
	/**
	 * @var list<array{
	 *   id: int,
	 *   beginColor: int,
	 *   vertices: list<int>,
	 *   barycentre: array{0: int, 1: int},
	 *   display: array{0: int, 1: int},
	 *   name: string,
	 *   danger: int,
	 *   points: int,
	 * }>
	 */
	public array $sectors;

	public int $scale = 20;

	public const DNG_CASUAL = 1;
	public const DNG_EASY = 2;
	public const DNG_MEDIUM = 3;
	public const DNG_HARD = 4;
	public const DNG_VERY_HARD = 5;

	/**
	 * @return list<int>
	 */
	public function getSectorVertices(int $i, int $scale = 1, int $xTranslate = 0): array
	{
		return array_map(
			fn (int $vertice) => ($vertice * $scale) + $xTranslate,
			$this->sectors[$i - 1]['vertices'],
		);
	}
}
