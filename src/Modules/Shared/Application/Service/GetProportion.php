<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Service;

final readonly class GetProportion
{
	/**
	 * @param array<mixed, int> $params
	 */
	public function __invoke(array $params, int $value): int
	{
		if (100 < $value) {
			throw new \LogicException('The requested value is greater than 100.');
		}

		$cursor = 0;
		$type = 0;
		$min = 0;
		$max = 0;

		$firstKey = array_key_first($params);
		$lastKey = array_key_last($params);

		foreach ($params as $key => $param) {
			if ($key === $firstKey) {
				$max = $param;
			} elseif ($lastKey !== $key) {
				$min = $cursor;
				$max = $cursor + $param;
			} else {
				$min = $cursor;
				$max = 100;
			}

			$cursor = $max;
			++$type;

			if ($value > $min && $value <= $max) {
				return $type;
			}
		}
		throw new \LogicException('Must return a value');
	}
}
