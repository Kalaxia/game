<?php

namespace App\Modules\Promethee\Domain\Repository;

use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<TechnologyQueue>
 */
interface TechnologyQueueRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): TechnologyQueue|null;

	public function getPlayerTechnologyQueue(Player $player, int $technology): TechnologyQueue|null;

	/**
	 * @return list<TechnologyQueue>
	 */
	public function getAll(): array;

	/**
	 * TODO Replace Place argument with OrbitalBase
	 * @return list<TechnologyQueue>
	 */
	public function getPlaceQueues(Place $place): array;

	/**
	 * @return list<TechnologyQueue>
	 */
	public function getPlayerQueues(Player $player): array;

	/**
	 * @return Collection<TechnologyQueue>
	 */
	public function matchPlayerQueuesSince(Player $player, \DateTimeImmutable $since): Collection;
}
