<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Service;

use App\Modules\Shared\Domain\Server\TimeMode;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class GameTimeConverter
{
	private const int GAME_CYCLE_SECONDS_FAST_MODE = 600;
	private const int GAME_CYCLE_SECONDS_STANDARD_MODE = 3600;

	public const STRATUM_CYCLES_COUNT = 2400;
	public const SEGMENT_CYCLES_COUNT = 24;

	private const array GAME_CYCLE_SECONDS = [
		TimeMode::Standard->name => self::GAME_CYCLE_SECONDS_STANDARD_MODE,
		TimeMode::Fast->name => self::GAME_CYCLE_SECONDS_FAST_MODE,
	];

	/**
	 * Server segment shift : Number of segments added to the server start date
	 *     Example : if the server started 2 days ago and the shift is 100, the date will be SEG102.
	 */
	public function __construct(
		private DurationHandler $durationHandler,
		#[Autowire('%server_start_time%')]
		private string $serverStartDate,
		#[Autowire('%server_segment_shift%')]
		private int $serverSegmentShift,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function convertSecondsToGameCycles(int $seconds): int
	{
		return intval(round($seconds / self::GAME_CYCLE_SECONDS[$this->timeMode->name]));
	}

	public function convertGameCyclesToSeconds(int $gameCycles): int
	{
		return self::GAME_CYCLE_SECONDS[$this->timeMode->name] * $gameCycles;
	}

	public function convertDatetimeToGameDate(string|\DateTimeImmutable $sourceDate, bool $returnHtml = true): string
	{
		$cycles = $this->getRel($sourceDate);
		$segments = floor($cycles / self::SEGMENT_CYCLES_COUNT);
		$remainingCycles = $cycles - ($segments * self::SEGMENT_CYCLES_COUNT);

		$return = 'SEG'.$segments.' REL'.$remainingCycles;

		return $returnHtml
			? '<span class="hb lt" title="'.$sourceDate->format('j.m.Y à H:i:s').'">'.$return.'</span>'
			: $return;
	}

	private function getRel(\DateTimeImmutable $sourceDate): int
	{
		$secondsDiff = $this->durationHandler->getDiff(new \DateTimeImmutable($this->serverStartDate), $sourceDate);

		return intval(
			floor($secondsDiff / self::GAME_CYCLE_SECONDS[$this->timeMode->name])
			+ ($this->serverSegmentShift * self::SEGMENT_CYCLES_COUNT)
		);
	}
}
