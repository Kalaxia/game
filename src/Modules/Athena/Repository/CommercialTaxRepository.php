<?php

declare(strict_types=1);

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\CommercialTaxRepositoryInterface;
use App\Modules\Athena\Model\CommercialTax;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class CommercialTaxRepository extends DoctrineRepository implements CommercialTaxRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, CommercialTax::class);
	}

	/**
	 * @throws NoResultException
	 */
	public function getFactionsTax(Color $faction, Color $relatedFaction): CommercialTax
	{
		return $this->findOneBy([
			'faction' => $faction,
			'relatedFaction' => $relatedFaction,
		]) ?? throw new NoResultException();
	}

	public function getFactionTaxesByImport(Color $faction): array
	{
		return $this->findBy([
			'faction' => $faction,
		], [
			'importTax' => 'ASC',
		]);
	}

	public function getFactionTaxesByExport(Color $faction): array
	{
		return $this->findBy([
			'faction' => $faction,
		], [
			'exportTax' => 'ASC',
		]);
	}
}
