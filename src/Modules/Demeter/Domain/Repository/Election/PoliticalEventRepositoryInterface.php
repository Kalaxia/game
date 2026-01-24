<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Repository\Election;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<PoliticalEvent>
 */
interface PoliticalEventRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): PoliticalEvent|null;

	public function getFactionLastPoliticalEvent(Color $faction): PoliticalEvent|null;

	/**
	 * @return list<PoliticalEvent>
	 */
	public function getFactionPoliticalEvents(Color $faction): array;
}
