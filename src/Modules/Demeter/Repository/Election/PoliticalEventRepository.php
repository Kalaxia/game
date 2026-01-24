<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Election;

use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<PoliticalEvent>
 */
class PoliticalEventRepository extends DoctrineRepository implements PoliticalEventRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, PoliticalEvent::class);
	}

	public function get(Uuid $id): PoliticalEvent|null
	{
		return $this->find($id);
	}

	public function getFactionLastPoliticalEvent(Color $faction): PoliticalEvent|null
	{
		return $this->findOneBy([
			'faction' => $faction,
		], [
			'startedAt' => 'DESC',
		]);
	}

	public function getFactionPoliticalEvents(Color $faction, $limit = 30): array
	{
		return $this->findBy(
			[
				'faction' => $faction,
			],
			orderBy: ['startedAt' => 'DESC'],
			limit: $limit,
		);
	}
}
