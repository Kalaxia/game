<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Application\Registry;

use App\Modules\Athena\Domain\Exception\NoCurrentBaseSetException;
use App\Modules\Galaxy\Domain\Entity\Planet;
use Symfony\Component\Uid\Uuid;

class CurrentPlayerPlanetsRegistry
{
	/**
	 * @var array<string, Planet>
	 */
	private array $planets = [];

	private ?Uuid $currentPlanetId = null;

	/**
	 * @param list<Planet> $planets
	 */
	public function setPlanets(array $planets): void
	{
		$this->planets = array_reduce($planets, function (array $acc, Planet $planet) {
			$acc[$planet->id->toBase32()] = $planet;

			return $acc;
		}, []);
	}

	public function get(Uuid $planetId): ?Planet
	{
		return $this->planets[$planetId->toBase32()] ?? null;
	}

	public function current(): Planet
	{
		if (null === $this->currentPlanetId) {
			throw new NoCurrentBaseSetException();
		}

		return $this->planets[$this->currentPlanetId->toBase32()];
	}

	public function first(): Planet
	{
		return array_values($this->planets)[0];
	}

	public function next(): ?Planet
	{
		$currentIndex = array_search($this->currentPlanetId->toBase32(), array_keys($this->planets));

		return array_values($this->planets)[$currentIndex + 1] ?? null;
	}

	public function setCurrentPlanet(Uuid $currentPlanetId): void
	{
		$this->currentPlanetId = $currentPlanetId;
	}

	public function count(): int
	{
		return count($this->planets);
	}

	/** @return array<string, Planet> */
	public function all(): array
	{
		return $this->planets;
	}
}
