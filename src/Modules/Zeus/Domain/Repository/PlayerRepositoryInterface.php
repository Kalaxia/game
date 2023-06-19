<?php

namespace App\Modules\Zeus\Domain\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Selectable;
use Symfony\Component\Uid\Uuid;

interface PlayerRepositoryInterface extends EntityRepositoryInterface
{
	public function get(int $id): Player|null;

	public function getByName(string $name): Player|null;

	public function getByBindKey(string $bindKey): Player|null;

	/**
	 * @return list<Player>
	 */
	public function getGodSons(Player $player): array;

	/**
	 * @param list<int> $ids
	 * @param list<int> $statements
	 * @return list<Player>
	 */
	public function getByIdsAndStatements(array $ids, array $statements): array;

	/**
	 * @param list<int> $statements
	 *
	 * @return list<Player>
	 */
	public function getByStatements(array $statements): array;

	public function countActivePlayers(): int;

	public function countAllPlayers(): int;

	public function countByFactionAndStatements(Color $faction, array $statements): int;

	public function getFactionAccount(Color $faction): Player;

	/**
	 * @return AbstractLazyCollection<int, Player>|Selectable<int, Player>
	 */
	public function getBySpecification(SelectorSpecification $specification): AbstractLazyCollection|Selectable;

	/**
	 * @return list<Player>
	 */
	public function getFactionPlayersByRanking(Color $faction): array;

	/**
	 * @return list<Player>
	 */
	public function getFactionPlayersByName(Color $faction): array;

	/**
	 * @return list<Player>
	 */
	public function getLastFactionPlayers(Color $faction): array;

	public function getGovernmentMember(Color $faction, int $status): Player|null;

	public function getFactionLeader(Color $faction): Player;

	/**
	 * @return list<Player>
	 */
	public function getActivePlayers(): array;

	/**
	 * @return list<Player>
	 */
	public function search(string $search): array;

	public function updatePlayerCredits(Player $player, int $credits): mixed;
}
