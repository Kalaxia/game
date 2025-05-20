<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Validator;

use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraint;

class IsActivePlayer extends Constraint implements SelectorSpecification
{
	public function addMatchingCriteria(QueryBuilder $queryBuilder): void
	{
		$queryBuilder->andWhere(
			$queryBuilder->expr()->in('p.statement', [Player::ACTIVE, Player::HOLIDAY])
		);
	}
}
