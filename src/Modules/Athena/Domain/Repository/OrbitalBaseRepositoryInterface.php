<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use Symfony\Component\Uid\Uuid;

interface OrbitalBaseRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): OrbitalBase|null;

	/**
	 * @return list<OrbitalBase>
	 */
	public function getBySpecification(SelectorSpecification $specification): array;

	/**
	 * @return list<OrbitalBase>
	 */
	public function getPlayerBases(Player $player): array;

	public function getPlayerBasesCount(Player $player): int;

	/**
	 * @return list<OrbitalBase>
	 */
	public function getSectorBases(Sector $sector): array;

	/**
	 * @return list<OrbitalBase>
	 */
	public function getSystemBases(System $system): array;
}
