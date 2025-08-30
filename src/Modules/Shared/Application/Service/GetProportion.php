<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Service;

final readonly class GetProportion
{
	/**
	 * @param list<int> $params
	 */
	public function __invoke(array $params, int $value): int
	{
		$cursor = 0;
		$type = 0;
		$min = 0;
		$max = 0;

		for ($i = 0; $i < count($params); ++$i) {
			if (0 == $i) {
				$max = $params[$i];
			} elseif ($i < count($params) - 1) {
				$min = $cursor;
				$max = $cursor + $params[$i];
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
