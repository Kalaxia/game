<?php

namespace App\Modules\Demeter\Domain\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

interface ColorRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ?Color;

	public function getOneByIdentifier(int $identifier): ?Color;

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
	 * @param list<int>          $regimes
	 * @param list<MandateState> $mandateStates
	 *
	 * @return list<Color>
	 */
	public function getByRegimesAndMandateStates(array $regimes, array $mandateStates): array;
}
