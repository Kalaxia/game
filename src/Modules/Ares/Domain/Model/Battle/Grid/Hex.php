<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle\Grid;

/**
 * A cell of a hex grid in axial coordinates.
 *
 * @see https://www.redblobgames.com/grids/hexagons/
 */
final readonly class Hex
{
	public function __construct(
		public int $q,
		public int $r,
	) {
	}

	public static function origin(): self
	{
		return new self(0, 0);
	}

	/**
	 * Third cube coordinate, so that q + r + s = 0.
	 */
	public function s(): int
	{
		return -$this->q - $this->r;
	}

	public function add(self $other): self
	{
		return new self($this->q + $other->q, $this->r + $other->r);
	}

	public function subtract(self $other): self
	{
		return new self($this->q - $other->q, $this->r - $other->r);
	}

	public function scale(int $factor): self
	{
		return new self($this->q * $factor, $this->r * $factor);
	}

	public function length(): int
	{
		return intdiv(abs($this->q) + abs($this->r) + abs($this->s()), 2);
	}

	public function distanceTo(self $other): int
	{
		return $this->subtract($other)->length();
	}

	public function neighbour(HexDirection $direction): self
	{
		return $this->add($direction->toHex());
	}

	/**
	 * @return list<self>
	 */
	public function neighbours(): array
	{
		return array_map(fn (HexDirection $direction) => $this->neighbour($direction), HexDirection::cases());
	}

	/**
	 * Rotates around the origin by steps of 60°, clockwise for positive values.
	 */
	public function rotate(int $sixths): self
	{
		[$x, $y, $z] = [$this->q, $this->s(), $this->r];

		for ($i = (($sixths % 6) + 6) % 6; $i > 0; --$i) {
			[$x, $y, $z] = [-$z, -$x, -$y];
		}

		return new self($x, $z);
	}

	public function rotateAround(self $center, int $sixths): self
	{
		return $this->subtract($center)->rotate($sixths)->add($center);
	}

	/**
	 * All cells within the given radius around this one, this one included.
	 *
	 * @return list<self>
	 */
	public function range(int $radius): array
	{
		$cells = [];

		for ($q = -$radius; $q <= $radius; ++$q) {
			for ($r = max(-$radius, -$q - $radius); $r <= min($radius, -$q + $radius); ++$r) {
				$cells[] = new self($this->q + $q, $this->r + $r);
			}
		}

		return $cells;
	}

	public function equals(self $other): bool
	{
		return $this->q === $other->q && $this->r === $other->r;
	}

	public function toKey(): string
	{
		return sprintf('%d,%d', $this->q, $this->r);
	}
}
