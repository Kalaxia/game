<?php

namespace App\Modules\Ares\Model;

use Symfony\Component\Uid\Uuid;

class Squadron implements \JsonSerializable
{
	/** @var list<Ship> */
	public array $ships = [];
	public bool $areShipsInitialized = false;

	public function __construct(
		public Uuid $id,
		public Commander $commander,
		public \DateTimeImmutable $createdAt,
		public \DateTimeImmutable $updatedAt,
		public int $lineCoord = 0,
		public int $position = 0, // position dans le tableau de l'armée
		public int|null $targetId = null,
		public int $ship0 = 0,
		public int $ship1 = 0,
		public int $ship2 = 0,
		public int $ship3 = 0,
		public int $ship4 = 0,
		public int $ship5 = 0,
		public int $ship6 = 0,
		public int $ship7 = 0,
		public int $ship8 = 0,
		public int $ship9 = 0,
		public int $ship10 = 0,
		public int $ship11 = 0,
	) {
	}

	public function setShips(array $ships): static
	{
		[
			$this->ship0,
			$this->ship1,
			$this->ship2,
			$this->ship3,
			$this->ship4,
			$this->ship5,
			$this->ship6,
			$this->ship7,
			$this->ship8,
			$this->ship9,
			$this->ship10,
			$this->ship11,
		] = $ships;

		return $this;
	}

	public function getShips(): array
	{
		return [
			$this->ship0,
			$this->ship1,
			$this->ship2,
			$this->ship3,
			$this->ship4,
			$this->ship5,
			$this->ship6,
			$this->ship7,
			$this->ship8,
			$this->ship9,
			$this->ship10,
			$this->ship11,
		];
	}

	public function getShipQuantity(int $shipNumber): int
	{
		return match ($shipNumber) {
			0 => $this->ship0,
			1 => $this->ship1,
			2 => $this->ship2,
			3 => $this->ship3,
			4 => $this->ship4,
			5 => $this->ship5,
			6 => $this->ship6,
			7 => $this->ship7,
			8 => $this->ship8,
			9 => $this->ship9,
			10 => $this->ship10,
			11 => $this->ship11,
			default => throw new \RuntimeException(sprintf('%d is not a valid ship number', $shipNumber)),
		};
	}

	public function setShipQuantity(int $shipNumber, int $quantity): void
	{
		match ($shipNumber) {
			0 => $this->ship0 = $quantity,
			1 => $this->ship1 = $quantity,
			2 => $this->ship2 = $quantity,
			3 => $this->ship3 = $quantity,
			4 => $this->ship4 = $quantity,
			5 => $this->ship5 = $quantity,
			6 => $this->ship6 = $quantity,
			7 => $this->ship7 = $quantity,
			8 => $this->ship8 = $quantity,
			9 => $this->ship9 = $quantity,
			10 => $this->ship10 = $quantity,
			11 => $this->ship11 = $quantity,
			default => throw new \RuntimeException(sprintf('%d is not a valid ship number', $shipNumber)),
		};
	}

	public function getShipsCount(): int
	{
		return count($this->ships);
	}

	// Move this method in dedicated handler
	public function getPev(): int
	{
		trigger_error('This method is deprecated and will be removed in the next major release. Use GetSquadronPev service instead.', E_USER_DEPRECATED);
	}

	public function isEmpty(): bool
	{
		return 0 === $this->getPev();
	}

	public function destructShip(int $key): void
	{
		$ship = $this->ships[$key];

		$this->setShipQuantity($ship->shipNumber, $this->getShipQuantity($ship->shipNumber) - 1);

		unset($this->ships[$key]);

		$this->ships = array_values($this->ships);
	}

	public function emptySquadron(): void
	{
		$this->setShips(array_fill(0, 12, 0));
	}

	public function jsonSerialize(): array
	{
		return [
			'lineCoord' => $this->lineCoord,
			'position' => $this->position,
			'ship0' => $this->ship0,
			'ship1' => $this->ship1,
			'ship2' => $this->ship2,
			'ship3' => $this->ship3,
			'ship4' => $this->ship4,
			'ship5' => $this->ship5,
			'ship6' => $this->ship6,
			'ship7' => $this->ship7,
			'ship8' => $this->ship8,
			'ship9' => $this->ship9,
			'ship10' => $this->ship10,
			'ship11' => $this->ship11,
		];
	}
}
