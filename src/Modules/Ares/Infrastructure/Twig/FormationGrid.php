<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig;

use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;

/**
 * Display of the formation grid (D9), shared by the fleet editors of the game and of the combat simulator.
 *
 * The front line is at the top. Offsets are stored with the engine convention (front facing east),
 * so the display rotates the grid by a quarter turn.
 */
final readonly class FormationGrid
{
	public function __construct(
		private int $radius,
		private int $cellSize = 34,
	) {
	}

	/**
	 * @template T
	 *
	 * @param callable(int $q, int $r): T $getSlot what stands on a cell
	 *
	 * @return list<array{q: int, r: int, left: float, top: float, slot: T}>
	 */
	public function getCells(callable $getSlot): array
	{
		$cells = [];
		$offset = sqrt(3) * $this->cellSize * $this->radius;

		foreach (Hex::origin()->range($this->radius) as $hex) {
			$cells[] = [
				'q' => $hex->q,
				'r' => $hex->r,
				// Pointy-top layout rotated by a quarter turn, so that the east (front) points up
				'left' => 1.5 * $this->cellSize * ($hex->r + $this->radius),
				'top' => $offset - sqrt(3) * $this->cellSize * ($hex->q + $hex->r / 2),
				'slot' => $getSlot($hex->q, $hex->r),
			];
		}

		return $cells;
	}

	/**
	 * @return array{width: float, height: float, cell_width: float, cell_height: float}
	 */
	public function getDimensions(): array
	{
		return [
			'width' => 1.5 * $this->cellSize * 2 * $this->radius + 2 * $this->cellSize,
			'height' => sqrt(3) * $this->cellSize * (2 * $this->radius + 1),
			'cell_width' => 2 * $this->cellSize,
			'cell_height' => sqrt(3) * $this->cellSize,
		];
	}

	public function isOnGrid(int $q, int $r): bool
	{
		return Hex::origin()->distanceTo(new Hex($q, $r)) <= $this->radius;
	}
}
