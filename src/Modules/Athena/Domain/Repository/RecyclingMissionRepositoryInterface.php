<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<RecyclingMission>
 */
interface RecyclingMissionRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?RecyclingMission;

	/**
	 * @return list<RecyclingMission>
	 */
	public function getAll(): array;

	/**
	 * @return list<RecyclingMission>
	 */
	public function getBaseMissions(Planet $base): array;

	/**
	 * @return list<RecyclingMission>
	 */
	public function getPlanetActiveMissions(Planet $planet): array;

	public function removePlanetMissions(Planet $planet): void;
}
