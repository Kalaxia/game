<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Organisms;

use App\Classes\Library\Format;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Application\PercentageApplier;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ElectionsDashboard',
	template: 'components/Faction/Organisms/ElectionsDashboard.html.twig'
)]
class ElectionsDashboard
{
	public Color $faction;
	public \DateTimeImmutable $campaignStartedAt;
	public \DateTimeImmutable $campaignEndedAt;
	public \DateTimeImmutable $electionEndedAt;
	public \DateTimeImmutable $mandateStartedAt;
	public \DateTimeImmutable $mandateEndedAt;
	public int $completeElectionTime;
	public int $remainingTimeUntilCampaign;
	public int $totalMandateTime;
	public int $remainingMandateTime;
	public int $timeUntilCampaign;
	public int $fullCampaignTime;

	public function __construct(
		private readonly ClockInterface $clock,
		private readonly NextElectionDateCalculator $nextElectionDateCalculator,
		private readonly DurationHandler $durationHandler,
	) {
	}

	public function mount(Color $faction): void
	{
		$this->faction = $faction;
		// time variables
		$this->campaignStartedAt = $this->nextElectionDateCalculator->getCampaignStartDate($faction);
		$this->campaignEndedAt = $this->nextElectionDateCalculator->getCampaignEndDate($faction);
		$this->electionEndedAt = $this->nextElectionDateCalculator->getBallotDate($faction);

		$this->mandateStartedAt = $this->nextElectionDateCalculator->getNextElectionDate($faction);
		$this->mandateEndedAt = $faction->isDemocratic()
			? $this->electionEndedAt
			: $this->campaignEndedAt;

		$now = $this->clock->now();
		$this->completeElectionTime = $this->durationHandler->getDiff($this->campaignStartedAt, $this->electionEndedAt);
		$this->remainingTimeUntilCampaign = $this->durationHandler->getDiff($now, $this->campaignStartedAt);

		$this->totalMandateTime = $this->durationHandler->getDiff($this->mandateStartedAt, $this->mandateEndedAt);
		$this->remainingMandateTime = $this->durationHandler->getDiff($this->mandateStartedAt, $now);
		$this->timeUntilCampaign = $this->durationHandler->getDiff($this->mandateStartedAt, $this->campaignStartedAt);

		$this->fullCampaignTime = $this->durationHandler->getDiff($this->campaignStartedAt, $this->campaignEndedAt);
	}

	public function getElectionProgress(): float
	{
		return $this->clock->now() > $this->campaignStartedAt
			? Format::percent($this->remainingTimeUntilCampaign, $this->completeElectionTime, false)
			: 0;
	}

	public function getCampaignPercent(): float
	{
		return PercentageApplier::toFloat($this->fullCampaignTime, $this->completeElectionTime);
	}

	public function getRemainingMandateTime1(): float
	{
		return (100 - PercentageApplier::toFloat($this->remainingTimeUntilCampaign, $this->totalMandateTime));
	}

	public function getRemainingMandateTimeAlt(): float
	{
		return PercentageApplier::toFloat($this->remainingMandateTime, $this->totalMandateTime);
	}
}
