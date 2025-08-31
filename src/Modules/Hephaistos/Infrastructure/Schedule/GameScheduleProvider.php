<?php

declare(strict_types=1);

namespace App\Modules\Hephaistos\Infrastructure\Schedule;

use App\Modules\Ares\Message\CommandersSchoolExperienceMessage;
use App\Modules\Atlas\Application\Message\RankingCreationMessage;
use App\Modules\Gaia\Application\Message\PlanetsUpdateMessage;
use App\Modules\Gaia\Message\PlacesUpdateMessage;
use App\Modules\Hephaistos\Message\DailyRoutineMessage;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Zeus\Message\PlayersCreditsUpdateMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('game')]
readonly class GameScheduleProvider implements ScheduleProviderInterface
{
	public function __construct(
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function getSchedule(): Schedule
	{
		[$hourlySchedule, $nightlySchedule] = match ($this->timeMode) {
			TimeMode::Standard => ['0 * * * *', '0 3 * * *'],
			TimeMode::Fast => ['*/10 * * * *', '0 * * * *'],
		};

		return (new Schedule())->add(
			// Hourly schedule
			RecurringMessage::cron($hourlySchedule, new PlayersCreditsUpdateMessage()),
			RecurringMessage::cron($hourlySchedule, new CommandersSchoolExperienceMessage()),
			RecurringMessage::cron($hourlySchedule, new PlacesUpdateMessage()),
			RecurringMessage::cron($hourlySchedule, new PlanetsUpdateMessage()),
			// Night schedule
			RecurringMessage::cron($nightlySchedule, new DailyRoutineMessage()),
			RecurringMessage::cron($nightlySchedule, new RankingCreationMessage()),
		);
	}
}
