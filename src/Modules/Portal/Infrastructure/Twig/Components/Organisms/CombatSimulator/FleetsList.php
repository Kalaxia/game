<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Twig\Components\Organisms\CombatSimulator;

use App\Modules\Ares\Application\Battle\BattleShipTypeRegistry;
use App\Modules\Ares\Application\Battle\GetBattleShipTypeName;
use App\Modules\Portal\Infrastructure\CombatSimulator\SimulatedFleetStorage;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Fleets of the combat simulator, by side.
 */
#[AsLiveComponent(
	name: 'SimulatorFleetsList',
	template: 'components/Organisms/Portal/CombatSimulator/FleetsList.html.twig',
)]
final class FleetsList
{
	use DefaultActionTrait;

	public function __construct(
		private readonly SimulatedFleetStorage $simulatedFleetStorage,
		private readonly BattleShipTypeRegistry $battleShipTypeRegistry,
		private readonly GetBattleShipTypeName $getBattleShipTypeName,
	) {
	}

	#[LiveListener('simulatedFleetSaved')]
	public function refresh(): void
	{
		// Fleets are read from the session at each render
	}

	#[LiveAction]
	public function duplicate(#[LiveArg] string $fleetId): void
	{
		if (null === ($fleet = $this->simulatedFleetStorage->get($fleetId))) {
			return;
		}

		$this->simulatedFleetStorage->save([
			...$fleet,
			'id' => Uuid::v4()->toRfc4122(),
			'name' => sprintf('%s (copie)', $fleet['name']),
		]);
	}

	#[LiveAction]
	public function remove(#[LiveArg] string $fleetId): void
	{
		$this->simulatedFleetStorage->remove($fleetId);
	}

	/**
	 * @return array<int, list<array<string, mixed>>> fleets by side
	 */
	public function getFleetsBySide(): array
	{
		$sides = [0 => [], 1 => []];

		foreach ($this->simulatedFleetStorage->all() as $fleet) {
			$sides[$fleet['side']][] = $fleet;
		}

		return $sides;
	}

	public function getPev(array $fleet): int
	{
		return array_sum(array_map(
			fn (array $slot) => $slot['ships'] * $this->battleShipTypeRegistry->get($slot['type'])->pev,
			$fleet['formation'],
		));
	}

	/**
	 * @return array<string, int> ship quantities by ship type name
	 */
	public function getShips(array $fleet): array
	{
		$ships = [];

		foreach ($fleet['formation'] as $slot) {
			$name = ($this->getBattleShipTypeName)($this->battleShipTypeRegistry->get($slot['type']));
			$ships[$name] = ($ships[$name] ?? 0) + $slot['ships'];
		}

		return $ships;
	}
}
