<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\CombatSimulator;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Fleets of the combat simulator, kept in the visitor's session as plain arrays.
 *
 * @phpstan-type SimulatedFleet array{
 *     id: string,
 *     name: string,
 *     side: int,
 *     faction: int,
 *     officer_level: int,
 *     arrival_turn: int,
 *     formation: list<array{type: string, ships: int, q: int, r: int}>,
 * }
 */
readonly class SimulatedFleetStorage
{
	private const string SESSION_KEY = 'combat_simulator.fleets';

	public function __construct(
		private RequestStack $requestStack,
	) {
	}

	/**
	 * @return list<SimulatedFleet>
	 */
	public function all(): array
	{
		return array_values($this->requestStack->getSession()->get(self::SESSION_KEY, []));
	}

	/**
	 * @return SimulatedFleet|null
	 */
	public function get(string $id): ?array
	{
		return array_find($this->all(), fn (array $fleet) => $fleet['id'] === $id);
	}

	/**
	 * @param SimulatedFleet $fleet
	 */
	public function save(array $fleet): void
	{
		$fleets = $this->all();
		$index = array_find_key($fleets, fn (array $existing) => $existing['id'] === $fleet['id']);

		if (null === $index) {
			$fleets[] = $fleet;
		} else {
			$fleets[$index] = $fleet;
		}

		$this->store($fleets);
	}

	public function remove(string $id): void
	{
		$this->store(array_values(array_filter($this->all(), fn (array $fleet) => $fleet['id'] !== $id)));
	}

	/**
	 * @param list<SimulatedFleet> $fleets
	 */
	private function store(array $fleets): void
	{
		$this->requestStack->getSession()->set(self::SESSION_KEY, $fleets);
	}
}
