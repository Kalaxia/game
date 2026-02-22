<?php

declare(strict_types=1);

namespace App\Modules\Promethee\Repository;

use App\Modules\Promethee\Domain\Repository\ResearchRepositoryInterface;
use App\Modules\Promethee\Model\Research;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;

class ResearchRepository extends DoctrineRepository implements ResearchRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Research::class);
	}

	public function getPlayerResearch(Player $player): ?Research
	{
		return $this->findOneBy([
			'player' => $player,
		]);
	}
}
