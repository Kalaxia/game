<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Service\Law;

use App\Modules\Shared\Domain\Service\GameTimeConverter;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\DatePoint;

final readonly class GetApplicationDuration
{
	public function __construct(
		private ClockInterface $clock,
		private GameTimeConverter $gameTimeConverter,
	) {
	}

	public function __invoke(int $type, ?int $cycles): \DateTimeImmutable
	{
		if (null === $cycles) {
			return $this->clock->now();
		}

		$seconds = $this->gameTimeConverter->convertGameCyclesToSeconds(max(1, $cycles));

		return new DatePoint('+'.$seconds.' seconds');
	}
}
