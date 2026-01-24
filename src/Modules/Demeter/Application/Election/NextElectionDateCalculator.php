<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Election;

use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class NextElectionDateCalculator
{
	public function __construct(
		private PoliticalEventRepositoryInterface $electionRepository,
		private DurationHandler                   $durationHandler,
		private ClockInterface                    $clock,
		private GetFactionsConfiguration          $getFactionsConfiguration,
		#[Autowire('%politics_campaign_duration%')]
		private int                               $campaignDuration,
		#[Autowire('%politics_election_duration%')]
		private int                               $electionDuration,
		#[Autowire('%politics_putsch_duration%')]
		private int                               $putschDuration,
		#[Autowire('%server_time_mode%')]
		private TimeMode                          $timeMode,
		#[Autowire('%server_start_time%')]
		private string $serverStartTime,
	) {
	}

	public function getBallotDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction, $this->getElectionDuration() + $this->getCampaignDuration());
	}

	public function getSenateUpdateMessage(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction);
	}

	public function getNextElectionDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction, addMandateDuration: false);
	}

	public function getStartDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction, $this->getElectionDuration(), false);
	}

	public function getEndDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction);
	}

	public function getPutschEndDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction, $this->getPutschDuration(), false);
	}

	public function getCampaignStartDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction);
	}

	public function getCampaignEndDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction, $this->getCampaignDuration());
	}

	public function getMandateDuration(Color $faction): int
	{
		return $this->timeMode->isStandard() ?
			($this->getFactionsConfiguration)($faction, 'mandateDuration')
			: 60 * 40;
	}

	private function calculate(Color $faction, int $duration = 0, bool $addMandateDuration = true): \DateTimeImmutable
	{
		$lastElection = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$durationEnd = null;

		if ($addMandateDuration) {
			$duration += $this->getMandateDuration($faction);
		}

		if (0 === $duration) {
			return $lastElection->startedAt ?? new \DateTimeImmutable($this->serverStartTime);
		}

		do {
			$durationStart = $durationEnd ?? $lastElection->startedAt ?? new \DateTimeImmutable($this->serverStartTime);

			$durationEnd = $this->durationHandler->getDurationEnd($durationStart, $duration);
		} while ($durationEnd < $this->clock->now());

		return $durationEnd;
	}

	private function getElectionDuration(): int
	{
		return $this->timeMode->isStandard() ? $this->electionDuration : 1200;
	}

	private function getCampaignDuration(): int
	{
		return $this->timeMode->isStandard() ? $this->campaignDuration : 300;
	}

	public function getPutschDuration(): int
	{
		return $this->timeMode->isStandard() ? $this->putschDuration : 1200;
	}
}
