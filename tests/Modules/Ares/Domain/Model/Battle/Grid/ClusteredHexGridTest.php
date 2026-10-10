<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Domain\Model\Battle\Grid;

use App\Modules\Ares\Domain\Model\Battle\Grid\ClusteredHexGrid;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClusteredHexGridTest extends TestCase
{
	/**
	 * @return \Generator<string, array{int}>
	 */
	public static function provideRadiuses(): \Generator
	{
		yield 'radius 1' => [1];
		yield 'radius 2' => [2];
		yield 'radius 4' => [4];
	}

	#[DataProvider('provideRadiuses')]
	public function testClusterHasExpectedCellCount(int $radius): void
	{
		$grid = new ClusteredHexGrid($radius);

		static::assertCount($grid->cellsPerCluster(), $grid->clusterCells(new Hex(2, -1)));
	}

	#[DataProvider('provideRadiuses')]
	public function testClustersTileTheBattlefieldWithoutOverlap(int $radius): void
	{
		$grid = new ClusteredHexGrid($radius);
		$owners = [];

		// A 5×5 battlefield of clusters
		for ($q = 0; $q < 5; ++$q) {
			for ($r = 0; $r < 5; ++$r) {
				$cluster = new Hex($q, $r);

				foreach ($grid->clusterCells($cluster) as $cell) {
					static::assertArrayNotHasKey($cell->toKey(), $owners, 'Clusters must not overlap');
					$owners[$cell->toKey()] = $cluster;

					static::assertTrue($grid->clusterOf($cell)->equals($cluster));
				}
			}
		}

		static::assertCount(25 * $grid->cellsPerCluster(), $owners);
	}

	#[DataProvider('provideRadiuses')]
	public function testEveryCellBelongsToExactlyOneCluster(int $radius): void
	{
		$grid = new ClusteredHexGrid($radius);

		foreach (Hex::origin()->range(3 * $radius + 3) as $cell) {
			$cluster = $grid->clusterOf($cell);

			static::assertLessThanOrEqual($radius, $grid->clusterCenter($cluster)->distanceTo($cell));
		}
	}

	#[DataProvider('provideRadiuses')]
	public function testNeighbourClustersAreAdjacent(int $radius): void
	{
		$grid = new ClusteredHexGrid($radius);
		$cluster = new Hex(1, 1);
		$center = $grid->clusterCenter($cluster);

		foreach ($cluster->neighbours() as $neighbour) {
			static::assertSame(2 * $radius + 1, $center->distanceTo($grid->clusterCenter($neighbour)));
		}
	}
}
