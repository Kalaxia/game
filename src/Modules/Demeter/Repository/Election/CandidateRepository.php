<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Election;

use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class CandidateRepository extends DoctrineRepository implements CandidateRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Candidate::class);
	}

	public function get(Uuid $id): ?Candidate
	{
		return $this->find($id);
	}

	public function getByElectionAndPlayer(Election $election, Player $player): ?Candidate
	{
		return $this->findOneBy([
			'player' => $player,
			'election' => $election,
		]);
	}

	public function getByElection(Election $election): array
	{
		return $this->findBy([
			'election' => $election,
		]);
	}
}
