<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Repository\Election;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Mandate;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Mandate>
 */
interface MandateRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?Mandate;

	public function getCurrentMandate(Color $faction): ?Mandate;

	public function getLastMandate(Color $faction): ?Mandate;

	/**
	 * @return list<Mandate>
	 */
	public function getFactionMandates(Color $faction): array;

	public function getMandateByElection(PoliticalEvent $politicalEvent): ?Mandate;
}
