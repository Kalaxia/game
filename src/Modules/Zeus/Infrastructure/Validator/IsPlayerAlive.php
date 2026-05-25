<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Validator;

use App\Modules\Zeus\Domain\Enum\PlayerStatement;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use App\Shared\Domain\Specification\Specification;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraint;

class IsPlayerAlive extends Constraint implements Specification, SelectorSpecification
{
	/**
	 * @param Player $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return $candidate->isAlive();
	}

	public function addMatchingCriteria(QueryBuilder $queryBuilder): void
	{
		$queryBuilder->andWhere(
			$queryBuilder->expr()->neq('p.statement', ':statement')
		)->setParameter('statement', PlayerStatement::Dead);
	}
}
