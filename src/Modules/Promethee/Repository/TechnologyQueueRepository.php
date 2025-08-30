<?php

declare(strict_types=1);

namespace App\Modules\Promethee\Repository;

use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class TechnologyQueueRepository extends DoctrineRepository implements TechnologyQueueRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, TechnologyQueue::class);
	}

	public function get(Uuid $id): TechnologyQueue|null
	{
		return $this->find($id);
	}

	public function getPlayerTechnologyQueue(Player $player, int $technology): TechnologyQueue|null
	{
		return $this->findOneBy([
			'player' => $player,
			'technology' => $technology,
		]);
	}

	public function getAll(): array
	{
		return $this->findAll();
	}

	public function getPlaceQueues(Place $place): array
	{
		return $this->findBy([
			'place' => $place,
		]);
	}

	public function getPlayerQueues(Player $player): array
	{
		return $this->findBy([
			'player' => $player,
		]);
	}

	public function matchPlayerQueuesSince(Player $player, \DateTimeImmutable $since): Collection
	{
		return $this->matching(new Criteria(Criteria::expr()->andX(
			Criteria::expr()->eq('player', $player),
			Criteria::expr()->gte('createdAt', $since),
		)));
	}
}
