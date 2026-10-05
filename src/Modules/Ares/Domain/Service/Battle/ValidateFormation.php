<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Service\Battle;

use App\Modules\Ares\Domain\Model\Battle\FormationSlot;
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
	 * @return list<string> violations, empty when the formation is valid
	 */
	public function __invoke(array $formation): array
	{
		$violations = [];
		$occupiedCells = [];

		if ([] === $formation) {
			$violations[] = 'The formation has no squadron';
		}

		foreach ($formation as $slot) {
			$cell = $slot->offset->toKey();

			if (Hex::origin()->distanceTo($slot->offset) > $this->compositionRadius) {
				$violations[] = sprintf('Cell %s is outside the formation grid', $cell);
			}

			if (isset($occupiedCells[$cell])) {
				$violations[] = sprintf('Cell %s holds more than one squadron', $cell);
			}
			$occupiedCells[$cell] = true;

			if ($slot->ships < 1 || $slot->ships > $slot->type->squadronSize) {
				$violations[] = sprintf(
					'A %s squadron holds 1 to %d ships, %d given on cell %s',
					$slot->type->identifier,
					$slot->type->squadronSize,
					$slot->ships,
					$cell,
				);
			}
		}

		return $violations;
	}
}
