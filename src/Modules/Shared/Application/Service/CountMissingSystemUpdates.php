<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Service;

use App\Modules\Shared\Domain\Model\SystemUpdatable;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class CountMissingSystemUpdates
{
	public function __construct(
		private ClockInterface $clock,
		private DurationHandler $durationHandler,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function __invoke(SystemUpdatable $updatable): int
	{
		if ($this->timeMode->isStandard()) {
			return $this->durationHandler->getHoursDiff($updatable->lastUpdatedBySystemAt(), $this->clock->now());
		}

		$secondsDiff = $this->durationHandler->getDiff($updatable->lastUpdatedBySystemAt(), $this->clock->now());

		return intval(round($secondsDiff / 600));
	}
}
