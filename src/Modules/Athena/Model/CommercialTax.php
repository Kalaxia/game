<?php

/**
 * CommercialTax.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 05.03.14
 */

namespace App\Modules\Athena\Model;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\Uid\Uuid;

class CommercialTax
{
	public function __construct(
		public Uuid $id,
		public Color $faction,
		public Color $relatedFaction,
		public int $exportTax,
		public int $importTax,
	) {
	}
}
