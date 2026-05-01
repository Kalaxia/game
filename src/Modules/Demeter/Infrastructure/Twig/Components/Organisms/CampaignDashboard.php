<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Organisms;

use App\Classes\Library\Format;
use App\Modules\Demeter\Model\Election\DemocraticElection;
use App\Modules\Demeter\Model\Election\DivineDesignation;
use App\Modules\Shared\Application\PercentageApplier;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'CampaignDashboard',
	template: 'components/Faction/Organisms/CampaignDashboard.html.twig'
)]
class CampaignDashboard
{
	public DemocraticElection|DivineDesignation $campaign;

	public function __construct(
		private readonly ClockInterface $clock,
		private readonly DurationHandler $durationHandler,
	) {
	}

	public function getPercent(): float
	{
		$campaignEndedAt = $this->campaign instanceof DemocraticElection
			? $this->campaign->campaignEndedAt
			: $this->campaign->endedAt;

		$campaignDuration = $this->durationHandler->getDiff(
			$this->campaign->startedAt,
			$campaignEndedAt,
		);
		$spentTime = $this->durationHandler->getDiff(
			$this->campaign->startedAt,
			$this->clock->now(),
		);

		return Format::percent(
			$spentTime,
			$campaignDuration,
		);
	}
}
