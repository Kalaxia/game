<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base;

use App\Modules\Shared\Domain\Server\TimeMode;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class GetCoolDownBeforeLeavingPlanet
{
	public function __construct(
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	/**
	 * @return int Cool down in hours
	 */
	public function __invoke(): int
	{
		return match ($this->timeMode) {
			TimeMode::Fast => 2,
			TimeMode::Standard => 12,
		};
	}
}
