<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Event\Commander;

use App\Modules\Ares\Model\Commander;
use App\Shared\Domain\Event\TrackingEvent;

readonly class SchoolAssignmentEvent implements TrackingEvent
{
	public function __construct(public Commander $commander)
	{
	}

	public function getTrackingPeopleId(): int
	{
		return $this->commander->player->id;
	}

	public function getTrackingEventName(): string
	{
		return 'School Assignment';
	}

	public function getTrackingData(): array
	{
		return [
			'commander_id' => $this->commander->id,
			'base_id' => $this->commander->base->id,
		];
	}
}
