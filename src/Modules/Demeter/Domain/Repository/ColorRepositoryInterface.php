<?php

namespace App\Modules\Demeter\Domain\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

interface ColorRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Color|null;

	public function getOneByIdentifier(int $identifier): Color|null;

	/**
	 * @return list<Color>
	 */
	public function getAll(): array;

	/**
	 * @return list<Color>
	 */
	public function getInGameFactions(): array;

	/**
	 * @return list<Color>
	 */
	public function getOpenFactions(): array;

	/**
	 * @return list<Color>
	 */
	public function getAllByActivePlayersNumber(): array;

	/**
	 * @return list<Color>
	 */
	public function getByRegimeAndElectionStatement($regimes, $electionStatements): array;
}
