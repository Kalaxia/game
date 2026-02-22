<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Election;

use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

class VoteRepository extends DoctrineRepository implements VoteRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Vote::class);
	}

	public function getPlayerVote(Player $player, Election $election): ?Vote
	{
		$qb = $this->createQueryBuilder('v');

		$qb->join('v.candidate', 'c')
			->where('c.election = :election')
			->andWhere('v.player = :player')
			->setParameter('election', $election->id, UuidType::NAME)
			->setParameter('player', $player);

		return $qb->getQuery()->getOneOrNullResult();
	}

	public function getElectionVotes(Election $election): array
	{
		$qb = $this->createQueryBuilder('v');

		$qb->join('v.candidate', 'c')
			->where('c.election = :election')
			->setParameter('election', $election->id, UuidType::NAME);

		return $qb->getQuery()->getResult();
	}
}
