<?php

namespace App\Modules\Zeus\Application\Handler\Bonus;

class TraceableBonusApplier extends AbstractBonusApplier
{
	private array $tracedBonuses = [];

	public function postApply(
		int $modifierId,
		float $modifierValue,
		float|int $initialValue,
		float $modifiedValue,
	): void {
		$this->tracedBonuses[] = [
			'modifier_id' => $modifierId,
			'modifier_value' => $modifierValue,
			'modified_value' => $modifiedValue,
			'initial_value' => $initialValue,
		];
	}

	public function getTracedBonuses(): array
	{
		return $this->tracedBonuses;
	}
}
