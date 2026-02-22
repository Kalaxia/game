<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Election;

use App\Modules\Demeter\Domain\Repository\Election\ElectionRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class ElectionRepository extends DoctrineRepository implements ElectionRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Election::class);
	}

	public function get(Uuid $id): ?Election
	{
		return $this->find($id);
	}

	public function getFactionLastElection(Color $faction): ?Election
	{
		return $this->findOneBy([
			'faction' => $faction,
		], [
			'dElection' => 'DESC',
		]);
	}
}
