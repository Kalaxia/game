<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\BattleShipType;
use App\Modules\Ares\Domain\Model\Battle\FormationSlot;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;
use App\Modules\Ares\Domain\Model\Battle\ShipRole;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Arranges ships automatically into single-type squadrons and a formation.
 *
 * Players compose their formations themselves (D9): this is the "fill automatically" suggestion
 * of the formation editor, and the formation of NPC fleets.
 * Squadrons are filled to their maximum size, then placed by role from the front line (facing east) to the rear.
 */
readonly class BuildFleetFormation
{
	/** Placement order from the front line to the rear */
	private const array ROLES_FROM_FRONT = [
		ShipRole::Line,
		ShipRole::Escort,
		ShipRole::Interceptor,
		ShipRole::Bomber,
		ShipRole::Capital,
		ShipRole::Artillery,
	];

	public function __construct(
		#[Autowire('%ares.battle.formation_radius%')]
		private int $compositionRadius = 2,
	) {
	}

	/**
	 * Number of squadrons a formation holds.
	 */
	public function getCapacity(): int
	{
		return count($this->getCellsFromFront());
	}

	/**
	 * @param list<array{BattleShipType, int}> $ships ship types and quantities provided by the player
	 *
	 * @return list<FormationSlot>
	 */
	public function __invoke(array $ships): array
	{
		$squadrons = $this->splitIntoSquadrons($ships);
		$cells = $this->getCellsFromFront();

		if (count($squadrons) > count($cells)) {
			throw new \DomainException(sprintf('The fleet needs %d squadrons but its formation holds %d at most', count($squadrons), count($cells)));
		}

		usort($squadrons, fn (array $a, array $b) => $this->getRoleRank($a[0]->role) <=> $this->getRoleRank($b[0]->role));

		return array_map(
			fn (array $squadron, Hex $cell) => new FormationSlot($squadron[0], $squadron[1], $cell),
			$squadrons,
			array_slice($cells, 0, count($squadrons)),
		);
	}

	public function getMaxSquadrons(): int
	{
		return count(Hex::origin()->range($this->compositionRadius));
	}

	/**
	 * @param list<array{BattleShipType, int}> $ships
	 *
	 * @return list<array{BattleShipType, int}>
	 */
	private function splitIntoSquadrons(array $ships): array
	{
		$squadrons = [];

		foreach ($ships as [$type, $quantity]) {
			for ($remaining = $quantity; $remaining > 0; $remaining -= $type->squadronSize) {
				$squadrons[] = [$type, min($remaining, $type->squadronSize)];
			}
		}

		return $squadrons;
	}

	/**
	 * Formation cells sorted from the front line (east) to the rear, center first within a line.
	 *
	 * @return list<Hex>
	 */
	private function getCellsFromFront(): array
	{
		$cells = Hex::origin()->range($this->compositionRadius);

		// In a pointy-top layout, the horizontal position is q + r/2 and the vertical one is r
		usort($cells, fn (Hex $a, Hex $b) => [-(2 * $a->q + $a->r), abs($a->r), $a->r] <=> [-(2 * $b->q + $b->r), abs($b->r), $b->r]);

		return $cells;
	}

	private function getRoleRank(ShipRole $role): int
	{
		return array_search($role, self::ROLES_FROM_FRONT, true);
	}
}
