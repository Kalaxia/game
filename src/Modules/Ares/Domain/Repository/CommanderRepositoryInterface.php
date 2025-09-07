<?php

namespace App\Modules\Ares\Domain\Repository;

use App\Modules\Ares\Model\Commander;
use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use Symfony\Component\Uid\Uuid;

interface CommanderRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Commander|null;

	/**
	 * @return list<Commander>
	 */
	public function getBySpecification(SelectorSpecification $specification): array;

	/**
	 * @param list<Uuid> $ids
	 *
	 * @return list<Commander>
	 */
	public function getCommandersByIds(array $ids = []): array;

	/**
	 * @return list<Commander>
	 */
	public function getMovingCommanders(): array;

	/**
	 * @param list<int>             $statements
	 * @param array<string, string> $orderBy
	 *
	 * @return list<Commander>
	 */
	public function getPlayerCommanders(Player $player, array $statements = [], array $orderBy = []): array;

	/**
	 * @param list<int>             $statements
	 * @param array<string, string> $orderBy
	 *
	 * @return list<Commander>
	 */
	public function getPlanetCommanders(Planet $planet, array $statements = [], array $orderBy = []): array;

	/**
	 * @return list<Commander>
	 */
	public function getIncomingAttacks(Player $player): array;

	/**
	 * @return list<Commander>
	 */
	public function getOutcomingAttacks(Player $player): array;

	/**
	 * @return list<Commander>
	 */
	public function getIncomingCommanders(Planet $place): array;

	/**
	 * @return list<Commander>
	 */
	public function getCommandersByLine(Planet $planet, int $line): array;

	public function countCommandersByLine(Planet $planet, int $line): int;

	/**
	 * @return array{nb: int, avgLevel: int}
	 */
	public function getFactionCommanderStats(Color $faction): array;
}
