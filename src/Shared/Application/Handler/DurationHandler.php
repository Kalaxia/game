<?php

declare(strict_types=1);

namespace App\Shared\Application\Handler;

use App\Shared\Domain\Model\DurationInterface;

class DurationHandler
{
	public function getHoursDiff(\DateTimeImmutable $from, \DateTimeImmutable $to): int
	{
		$diff = $from->diff($to);

		return $diff->h + ($diff->days * 24);
	}

	public function getDiff(\DateTimeImmutable $from, \DateTimeImmutable $to): int
	{
		return $to->getTimestamp() - $from->getTimestamp();
	}

	public function getDurationRemainingTime(DurationInterface $duration): int
	{
		return $this->getRemainingTime($duration->getEndDate());
	}

	public function getRemainingTime(\DateTimeImmutable $date)
	{
		return max($date->getTimestamp() - time(), 0);
	}

	public function getDurationEnd(\DateTimeImmutable $startedAt, int $seconds): \DateTimeImmutable
	{
		return \DateTimeImmutable::createFromMutable(
			\DateTime::createFromImmutable($startedAt)
				->modify(sprintf('+%d seconds', $seconds))
		);
	}
}
