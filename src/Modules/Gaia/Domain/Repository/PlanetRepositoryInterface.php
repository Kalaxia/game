<?php

namespace App\Modules\Gaia\Domain\Repository;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Planet>
 */
interface PlanetRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return Collection<Planet>
	 */
	public function getAll(): Collection;

	public function get(Uuid $id): Planet|null;

	/**
	 * @return list<Planet>
	 */
	public function getBySpecification(SelectorSpecification $specification): array;

	/**
	 * @return list<Planet>
	 */
	public function getPlayerPlanets(Player $player): array;

	public function countPlayerPlanets(Player $player): int;

	/**
	 * @return list<Planet>
	 */
	public function getSectorPlanets(Sector $sector): array;

	/**
	 * @return list<Planet>
	 */
	public function getSystemPlanets(System $system): array;

	/**
	 * @return list<Uuid>
	 */
	public function getCandidatePlanetsForNewPlayers(Sector $sector): array;
}
