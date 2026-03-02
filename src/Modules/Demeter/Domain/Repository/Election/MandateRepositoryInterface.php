<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Repository\Election;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Mandate;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<Mandate>
 */
interface MandateRepositoryInterface extends EntityRepositoryInterface
{
	public function getCurrentMandate(Color $faction): ?Mandate;

	public function getLastMandate(Color $faction): ?Mandate;
}
