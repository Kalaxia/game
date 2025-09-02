<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Specification;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraint;

class CanProposeCommercialRoute extends Constraint implements SelectorSpecification
{
	public function __construct(
		private readonly Player $player,
		private readonly Planet $planet,
		private readonly array  $factions,
		private readonly int    $minDistance,
		private readonly int    $maxDistance,
		array|null              $groups = null,
		mixed                   $payload = null,
	) {
		parent::__construct([], $groups, $payload);
	}

	public function addMatchingCriteria(QueryBuilder $queryBuilder): void
	{
		$queryBuilder->andWhere('ob.player = :player')
			->andWhere('ob.levelSpatioport > 0')
			->andWhere($queryBuilder->expr()->in('p.faction', ':factions'))
			->andWhere('(FLOOR(SQRT(POW(:system_x - s.xPosition, 2) + POW(:system_y - s.yPosition, 2)))) >= :min_distance')
			->andWhere('(FLOOR(SQRT(POW(:system_x - s.xPosition, 2) + POW(:system_y - s.yPosition, 2)))) <= :max_distance')
			->setParameter('player', $this->player)
			->setParameter('system_x', $this->planet->system->xPosition)
			->setParameter('system_y', $this->planet->system->yPosition)
			->setParameter('factions', $this->factions)
			->setParameter('min_distance', $this->minDistance)
			->setParameter('max_distance', $this->maxDistance);
	}
}
