<?php

namespace App\Modules\Ares\Domain\Repository;

use App\Modules\Ares\Model\Report;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Report>
 */
interface ReportRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Report|null;

	/**
	 * @return list<Report>
	 */
	public function getByAttackerAndPlace(Player $attacker, Planet $place, \DateTimeImmutable $dFight): array;

	/**
	 * @param Uuid[] $places
	 * @return Report[]
	 */
	public function getAttackReportsByPlaces(Player $attacker, array $places): array;

	public function removePlayerReports(Player $player): void;
}
