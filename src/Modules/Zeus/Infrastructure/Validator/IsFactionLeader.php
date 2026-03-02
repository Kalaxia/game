<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Validator;

use App\Modules\Demeter\Model\Color;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorCompositeSpecification;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraint;

class IsFactionLeader extends SelectorCompositeSpecification
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
			new IsActiveFactionPlayer($this->faction),
			new HasStatus([Player::CHIEF]),
		];
	}

	#[\Override]
	public function addMatchingCriteria(QueryBuilder $queryBuilder): void
	{
		parent::addMatchingCriteria($queryBuilder);

		$queryBuilder->addOrderBy('p.status', 'DESC');
	}
}
