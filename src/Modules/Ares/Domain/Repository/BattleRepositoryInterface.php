<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Repository;

use App\Modules\Ares\Domain\Model\Battle\Battle;
use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Battle>
 */
interface BattleRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?Battle;

	/**
	 * The battle going on in orbit of a planet: there is at most one (Q1, D7).
	 */
	public function getRunningBattleAt(Planet $planet): ?Battle;

	/**
	 * @return list<Battle> the battles the player took part in, latest first
	 */
	public function getPlayerBattles(Player $player, int $limit = 50): array;

	/**
	 * @param list<Uuid> $placeIds
	 *
	 * @return list<Battle> the battles the player took part in at these places, latest first
	 */
	public function getPlayerBattlesAt(Player $player, array $placeIds): array;

	/**
	 * @return list<Battle> the battles members of the faction took part in, latest first
	 */
	public function getFactionBattles(Color $faction, int $limit = 20): array;
}
