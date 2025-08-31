<?php

namespace App\Modules\Athena\Domain\Specification;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Shared\Domain\Specification\Specification;

readonly class CanLeavePlanet implements Specification
{
	public function __construct(private int $coolDownHours)
	{
	}

	/**
	 * @param Planet $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		$diff = (new \DateTime())->diff($candidate->createdAt);

		return $diff->format('%a') > 1 || $diff->format('%H') >= $this->coolDownHours;
	}
}
