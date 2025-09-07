<?php

namespace App\Modules\Ares\Domain\Repository;

use App\Modules\Ares\Model\Report;
use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Report>
 */
interface LiveReportRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Report|null;

	/**
	 * @return list<Report>
	 */
	public function getPlayerReports(Player $player): array;

	/**
	 * @param list<Planet> $places
	 *
	 * @return list<Report>
	 */
	public function getAttackReportsByPlaces(Player $player, array $places): array;

	/**
	 * @return list<Report>
	 */
	public function getAttackReportsByMode(Player $player, bool $hasRebels, bool $isArchived): array;

	/**
	 * @return list<Report>
	 */
	public function getDefenseReportsByMode(Player $player, bool $hasRebels, bool $isArchived): array;

	/**
	 * @return list<Report>
	 */
	public function getFactionAttackReports(Color $faction): array;

	/**
	 * @return list<Report>
	 */
	public function getFactionDefenseReports(Color $faction): array;
}
