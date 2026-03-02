<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Election;

use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends DoctrineRepository<Vote>
 */
class VoteRepository extends DoctrineRepository implements VoteRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Vote::class);
	}

	public function getPlayerVote(Player $player, PoliticalEvent $politicalEvent): ?Vote
	{
		$qb = $this->createQueryBuilder('v');

		$qb->join('v.candidate', 'c')
			->where('c.politicalEvent = :politicalEvent')
			->andWhere('v.player = :player')
			->setParameter('politicalEvent', $politicalEvent->id, UuidType::NAME)
			->setParameter('player', $player);

		return $qb->getQuery()->getOneOrNullResult();
	}

	public function getPoliticalEventVotes(PoliticalEvent $politicalEvent): array
	{
		$qb = $this->createQueryBuilder('v');

		$qb->join('v.candidate', 'c')
			->where('c.politicalEvent = :politicalEvent')
			->setParameter('politicalEvent', $politicalEvent->id, UuidType::NAME);

		return $qb->getQuery()->getResult();
	}
}
