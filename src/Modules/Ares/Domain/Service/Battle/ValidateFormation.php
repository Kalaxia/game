<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\FormationSlot;
use App\Modules\Ares\Domain\Model\Battle\FormationViolation;
use App\Modules\Ares\Domain\Model\Battle\Grid\Hex;

/**
 * Checks a formation composed by a player (D9).
 *
 * A formation is valid when every squadron stands on the composition grid, alone on its cell,
 * with a number of ships allowed by its type.
 */
readonly class ValidateFormation
{
	public function __construct(
		private int $compositionRadius = 2,
	) {
	}

	/**
	 * @param list<FormationSlot> $formation
	 *
	 * @return list<FormationViolation> empty when the formation is valid
	 */
	public function __invoke(array $formation): array
	{
		$violations = [];
		$occupiedCells = [];

		if ([] === $formation) {
			$violations[] = new FormationViolation(FormationViolation::EMPTY);
		}

		foreach ($formation as $slot) {
			$cell = $slot->offset->toKey();

			if (Hex::origin()->distanceTo($slot->offset) > $this->compositionRadius) {
				$violations[] = new FormationViolation(FormationViolation::OUTSIDE_GRID, ['cell' => $cell]);
			}

			if (isset($occupiedCells[$cell])) {
				$violations[] = new FormationViolation(FormationViolation::CELL_TAKEN, ['cell' => $cell]);
			}
			$occupiedCells[$cell] = true;

			if ($slot->ships < 1 || $slot->ships > $slot->type->squadronSize) {
				$violations[] = new FormationViolation(FormationViolation::SQUADRON_SIZE, [
					'type' => $slot->type->identifier,
					'max' => $slot->type->squadronSize,
					'ships' => $slot->ships,
					'cell' => $cell,
				]);
			}
		}

		return $violations;
	}
}
