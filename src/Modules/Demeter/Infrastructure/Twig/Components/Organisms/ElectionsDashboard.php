<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Organisms;

use App\Classes\Library\Format;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\Election\MandateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Mandate;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ElectionsDashboard',
	template: 'components/Faction/Organisms/ElectionsDashboard.html.twig'
)]
class ElectionsDashboard
{
	public Color $faction;
	public Mandate $currentMandate;
	public ?PoliticalEvent $lastPoliticalEvent;
	public \DateTimeImmutable $campaignStartedAt;
	public \DateTimeImmutable $campaignEndedAt;
	public \DateTimeImmutable $electionEndedAt;
	public \DateTimeImmutable $mandateStartedAt;
	public \DateTimeImmutable $mandateEndedAt;
	public int $totalMandateTime;
	public int $currentMandateTime;
	public int $timeUntilCampaign;
	public int $electionTime;

	public function __construct(
		private readonly ClockInterface $clock,
		private readonly MandateRepositoryInterface $mandateRepository,
		private readonly PoliticalEventRepositoryInterface $politicalEventRepository,
		private readonly NextElectionDateCalculator $nextElectionDateCalculator,
		private readonly DurationHandler $durationHandler,
	) {
	}

	public function mount(Color $faction): void
	{
		$this->faction = $faction;
		$this->currentMandate = $this->mandateRepository->getCurrentMandate($faction)
			?? throw new \RuntimeException(sprintf('No mandate found for faction %s.', $faction->identifier));
		$this->lastPoliticalEvent = $this->politicalEventRepository->getFactionLastPoliticalEvent($faction);
		$mandateDuration = $this->nextElectionDateCalculator->getMandateDuration($faction);
		$this->mandateStartedAt = $this->currentMandate->startedAt;
		// time variables
		$this->campaignStartedAt = $this->durationHandler->getDurationEnd(
			$this->mandateStartedAt,
			$mandateDuration,
		);
		$this->campaignEndedAt = $this->durationHandler->getDurationEnd(
			$this->campaignStartedAt,
			$this->nextElectionDateCalculator->getCampaignDuration(),
		);
		$this->electionEndedAt = $this->lastPoliticalEvent?->endedAt
			?? $this->durationHandler->getDurationEnd(
				$this->campaignStartedAt,
				$this->nextElectionDateCalculator->getCampaignDuration()
				+ ($faction->isDemocratic())
					? $this->nextElectionDateCalculator->getElectionDuration()
					: 0,
			);

		$this->mandateEndedAt = $this->currentMandate->expiredAt;

		$now = $this->clock->now();

		$this->totalMandateTime = $this->durationHandler->getDiff($this->mandateStartedAt, $this->mandateEndedAt);
		$this->timeUntilCampaign = $this->durationHandler->getDiff($this->mandateStartedAt, $this->campaignStartedAt);

		$this->currentMandateTime = $this->durationHandler->getDiff($this->mandateStartedAt, $now);
		$this->electionTime = $this->durationHandler->getDiff($this->mandateStartedAt, $this->campaignEndedAt);
	}

	public function getCampaignStartPosition(): float
	{
		return Format::percent(
			$this->timeUntilCampaign,
			$this->totalMandateTime,
		);
	}

	public function getCampaignCurrentPosition(): float
	{
		return Format::percent(
			$this->currentMandateTime - $this->timeUntilCampaign,
			$this->totalMandateTime - $this->timeUntilCampaign,
		);
	}

	public function getElectionStartPosition(): float
	{
		return Format::percent(
			$this->electionTime,
			$this->totalMandateTime,
		);
	}

	public function getRemainingMandateTimeAlt(): float
	{
		return Format::percent(
			$this->currentMandateTime,
			$this->totalMandateTime,
		);
	}
}
