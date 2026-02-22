<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Law;

use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class LawRepository extends DoctrineRepository implements LawRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Law::class);
	}

	public function get(Uuid $id): ?Law
	{
		return $this->find($id);
	}

	public function getByFactionAndStatements(Color $faction, array $statements = []): array
	{
		return $this->findBy([
			'faction' => $faction,
			'statement' => $statements,
		], [
			'createdAt' => 'DESC',
		]);
	}

	public function lawExists(Color $faction, int $type): bool
	{
		return $this->count([
			'faction' => $faction,
			'type' => $type,
			'statement' => [Law::EFFECTIVE, Law::VOTATION],
		]) > 0;
	}
}
