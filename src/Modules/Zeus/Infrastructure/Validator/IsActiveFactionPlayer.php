<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Validator;

use App\Modules\Demeter\Model\Color;
use App\Shared\Domain\Specification\SelectorCompositeSpecification;
use Symfony\Component\Validator\Constraint;

class IsActiveFactionPlayer extends SelectorCompositeSpecification
{
	public function __construct(private readonly Color $faction)
	{
		parent::__construct();
	}

	/**
	 * @param array<string, mixed> $options
	 *
	 * @return Constraint[]
	 */
	public function getConstraints(array $options): array
	{
		return [
			new IsActivePlayer(),
			new IsFromFaction($this->faction),
		];
	}
}
