<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\CommercialTax;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

interface CommercialTaxRepositoryInterface extends EntityRepositoryInterface
{
	public function getFactionsTax(Color $faction, Color $relatedFaction): CommercialTax;

	/**
	 * @return list<CommercialTax>
	 */
	public function getFactionTaxesByImport(Color $faction): array;

	/**
	 * @return list<CommercialTax>
	 */
	public function getFactionTaxesByExport(Color $faction): array;
}
