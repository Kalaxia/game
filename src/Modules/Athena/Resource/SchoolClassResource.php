<?php

namespace App\Modules\Athena\Resource;

class SchoolClassResource
{
	/**
	 * @var list<array<string, mixed>>
	 */
	private static array $classes = [
		[
			'credit' => 2500,
			'minSize' => 1,
			'maxSize' => 1,
			'minExp' => 100,
			'maxExp' => 210,
			'point' => 5,
			'title' => 'Engager un officier nul',
		],
		[
			'credit' => 2500,
			'minSize' => 1,
			'maxSize' => 1,
			'minExp' => 100,
			'maxExp' => 210,
			'point' => 5,
			'title' => 'Engager un officier nul',
		],
		[
			'credit' => 2500,
			'minSize' => 1,
			'maxSize' => 1,
			'minExp' => 100,
			'maxExp' => 210,
			'point' => 5,
			'title' => 'Engager un officier nul',
		],
	];

	public static function getInfo(int $i, string $info): mixed
	{
		if (!in_array($info, ['credit', 'minSize', 'maxSize', 'minExp', 'maxExp', 'point', 'title'])) {
			throw new \LogicException('info inconnue dans getInfo de SchoolClassResource');
		}
		if ($i < self::size()) {
			return self::$classes[$i][$info];
		}

		return false;
	}

	public static function size(): int
	{
		return count(self::$classes);
	}
}
