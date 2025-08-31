<?php

namespace App\Modules\Athena\Model;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Shared\Domain\Model\QueueableInterface;
use Symfony\Component\Uid\Uuid;

class BuildingQueue implements QueueableInterface
{
	public function __construct(
		public Uuid                    $id,
		public Planet                  $base,
		public int                     $buildingNumber,
		public int                     $targetLevel,
		public \DateTimeImmutable      $startedAt,
		public \DateTimeImmutable|null $endedAt = null,
	) {
			
	}

	public function getStartDate(): \DateTimeImmutable
	{
		return $this->startedAt;
	}

	public function getEndDate(): \DateTimeImmutable
	{
		return $this->endedAt ?? throw new \RuntimeException('Ending date cannot be null');
	}

	public function getResourceIdentifier(): int
	{
		return $this->buildingNumber;
	}
}
