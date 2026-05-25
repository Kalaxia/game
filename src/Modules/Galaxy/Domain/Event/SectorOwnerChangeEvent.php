<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Domain\Event;

use App\Modules\Demeter\Model\Color;
use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Hermes\Application\Builder\NewsBuilder;
use App\Modules\Hermes\Domain\Enum\NewsType;
use App\Modules\Hermes\Domain\Event\NewsEvent;
use App\Shared\Domain\Event\TrackingEvent;

final readonly class SectorOwnerChangeEvent implements NewsEvent, TrackingEvent
{
	public function __construct(
		public Sector $sector,
		public ?Color $winner = null,
		public ?Color $loser = null,
	) {
	}

	public function getTrackingData(): array
	{
		return [
			'sector_number' => $this->sector->identifier,
			'loser_identifier' => $this->loser?->identifier,
			'winner_identifier' => $this->winner?->identifier,
		];
	}

	public function getTrackingEventName(): string
	{
		return 'Sector owner change';
	}

	public function getTrackingPeopleId(): ?int
	{
		return null;
	}

	public function getNewsBuilders(array $factions): \Generator
	{
		$data = [
			'sectorIdentifier' => $this->sector->identifier,
			'sectorName' => $this->sector->name,
			'loserIdentifier' => $this->loser?->identifier,
			'winnerIdentifier' => $this->winner?->identifier,
		];

		if (null !== $this->winner) {
			yield NewsBuilder::new()
				->fromFaction($this->winner)
				->forFaction($this->winner)
				->setType(NewsType::SectorWon)
				->setData($data);
		}

		if (null !== $this->loser) {
			yield NewsBuilder::new()
				->fromFaction($this->loser)
				->forFaction($this->loser)
				->setType(NewsType::SectorLost)
				->setData($data);
		}

		yield NewsBuilder::new()
			->fromFaction($this->winner ?? $this->loser)
			->forFactions(array_filter(
				$factions,
				fn (Color $faction) => !in_array($faction->identifier, [
					$this->loser?->identifier,
					$this->winner?->identifier,
				]),
			))
			->setType(NewsType::SectorOwnerChange)
			->setData($data);
	}
}
