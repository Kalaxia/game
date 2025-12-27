<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Validator;

use App\Modules\Portal\Domain\Entity\User;
use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraint;

class DoesPlayerBelongTo extends Constraint implements SelectorSpecification
{
	public function __construct(private readonly User $user)
	{
		parent::__construct();
	}

	public function addMatchingCriteria(QueryBuilder $queryBuilder): void
	{
		$queryBuilder->andWhere($queryBuilder->expr()->eq('p.user', ':user'))
			->setParameter('user', $this->user);
	}
}
