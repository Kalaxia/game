<?php

namespace App\Modules\Athena\Model;

use App\Shared\Domain\Model\QueueableInterface;
use Symfony\Component\Uid\Uuid;

class BuildingQueue implements QueueableInterface
{
	public function __construct(
		public Uuid $id,
		public OrbitalBase $base,
		public int $buildingNumber,
		public int $targetLevel,
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
		return $this->buildingNumber;
	}
}
