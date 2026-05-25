<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Event;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Hermes\Application\Builder\NewsBuilder;
use App\Modules\Hermes\Domain\Enum\NewsType;
use App\Modules\Hermes\Domain\Event\NewsEvent;
use App\Shared\Domain\Event\TrackingEvent;

final readonly class NewCandidateEvent implements NewsEvent, TrackingEvent
{
	public function __construct(
		public PoliticalEvent $event,
		public Candidate $candidate,
		public array $otherCandidates,
	) {
	}

	public function getTrackingEventName(): string
	{
		return 'New Candidate';
	}

	public function getTrackingPeopleId(): int
	{
		return $this->candidate->player->id;
	}

	public function getTrackingData(): array
	{
		return [
			'faction_identifier' => $this->event->faction->identifier,
			'faction_regime' => $this->event->faction->regime,
			'event_id' => $this->event->id,
			'event_type' => $this->event->getType()->value,
		];
	}

	public function getNewsBuilders(array $factions): \Generator
	{
		$data = [
			'factionRegime' => $this->event->faction->regime,
			'factionIdentifier' => $this->event->faction->identifier,
			'candidateId' => $this->candidate->player->id,
			'candidateName' => $this->candidate->player->name,
			'candidateStatus' => $this->candidate->player->status,
			'candidateGender' => $this->candidate->player->getGender(),
			'otherCandidatesCount' => count($this->otherCandidates),
			'otherCandidates' => array_map(
				fn (Candidate $candidate) => [
					'id' => $candidate->id,
					'name' => $candidate->player->name,
					'status' => $candidate->player->status,
					'gender' => $candidate->player->getGender(),
				],
				$this->otherCandidates,
			),
			'program' => $this->candidate->program,
		];

		yield NewsBuilder::new()
			->fromFaction($this->event->faction)
			->setType(NewsType::NewCandidate)
			->setData($data)
			->forFaction($this->event->faction);

		yield NewsBuilder::new()
			->fromFaction($this->event->faction)
			->setType(NewsType::ForeignNewCandidate)
			->setData($data)
			->forFactions(array_filter(
				$factions,
				fn (Color $f) => $f->identifier !== $this->event->faction->identifier,
			));
	}
}
