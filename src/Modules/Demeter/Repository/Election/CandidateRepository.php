<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Election;

use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Candidate>
 */
class CandidateRepository extends DoctrineRepository implements CandidateRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Candidate::class);
	}

	public function get(Uuid $id): Candidate|null
	{
		return $this->find($id);
	}

	public function getByPoliticalEventAndPlayer(PoliticalEvent $politicalEvent, Player $player): Candidate|null
	{
		return $this->findOneBy([
			'player' => $player,
			'politicalEvent' => $politicalEvent,
		]);
	}

	public function getByPoliticalEvent(PoliticalEvent $politicalEvent): array
	{
		return $this->findBy([
			'politicalEvent' => $politicalEvent,
		]);
	}
}
