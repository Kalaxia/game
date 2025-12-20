<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Economy\Domain\Entity\Company;
use App\Modules\Economy\Domain\Enum\Activity;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Company>
 */
interface CompanyRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Company|null;

	/**
	 * @return list<Company>
	 */
	public function searchCandidateCompaniesForNewActivity(
		Activity    $activity,
		Color|null  $faction = null,
		int         $limit = 12,
	): array;
}
