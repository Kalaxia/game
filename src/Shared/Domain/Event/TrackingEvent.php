<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

interface TrackingEvent
{
	public function getTrackingPeopleId(): ?int;

	public function getTrackingEventName(): string;

	/** @return array<string, mixed> */
	public function getTrackingData(): array;
}
