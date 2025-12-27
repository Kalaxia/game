<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Validator;

use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraint;

class HasStatus extends Constraint implements SelectorSpecification
{
	public function __construct(private readonly array $statuses, ?array $groups = null, mixed $payload = null)
	{
		parent::__construct(
			groups: $groups,
			payload: $payload,
		);
	}

	public function addMatchingCriteria(QueryBuilder $queryBuilder): void
	{
		$queryBuilder->andWhere($queryBuilder->expr()->in('p.status', ':statuses'))
			->setParameter('statuses', $this->statuses);
	}
}
