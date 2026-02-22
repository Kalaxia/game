<?php

/**
 * Technology Queue.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 10.02.14
 */

namespace App\Modules\Promethee\Model;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Model\QueueableInterface;
use Symfony\Component\Uid\Uuid;

class TechnologyQueue implements QueueableInterface
{
	public function __construct(
		public Uuid $id,
		public Player $player,
		public Planet $place,
		public int $technology,
		public int $targetLevel,
		public \DateTimeImmutable $createdAt,
		public \DateTimeImmutable $startedAt,
		public \DateTimeImmutable $endedAt,
	) {
	}

	public function getStartDate(): \DateTimeImmutable
	{
		return $this->startedAt;
	}

	public function getEndDate(): \DateTimeImmutable
	{
		return $this->endedAt;
	}

	public function getResourceIdentifier(): int
	{
		return $this->technology;
	}
}
