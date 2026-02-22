<?php

namespace App\Modules\Demeter\Domain\Repository\Election;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Election>
 */
interface ElectionRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?Election;

	public function getFactionLastElection(Color $faction): ?Election;
}
