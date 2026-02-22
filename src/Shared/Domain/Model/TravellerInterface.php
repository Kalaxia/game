<?php

namespace App\Shared\Domain\Model;

interface TravellerInterface
{
	public function isMoving(): bool;

	public function getDepartureDate(): ?\DateTimeImmutable;

	public function getArrivalDate(): ?\DateTimeImmutable;
}
