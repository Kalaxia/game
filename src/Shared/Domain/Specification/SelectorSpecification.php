<?php

namespace App\Shared\Domain\Specification;

use Doctrine\ORM\QueryBuilder;

interface SelectorSpecification
{
	public function addMatchingCriteria(QueryBuilder $queryBuilder): void;
}
