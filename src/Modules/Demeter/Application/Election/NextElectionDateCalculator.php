<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Election;

use App\Modules\Demeter\Domain\Repository\Election\MandateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Shared\Application\Handler\DurationHandler;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class NextElectionDateCalculator
{
	public function __construct(
		private PoliticalEventRepositoryInterface $politicalEventRepository,
		private MandateRepositoryInterface $mandateRepository,
		private DurationHandler $durationHandler,
		private ClockInterface $clock,
		private GetFactionsConfiguration $getFactionsConfiguration,
		#[Autowire('%politics_campaign_duration%')]
		private int $campaignDuration,
		#[Autowire('%politics_election_duration%')]
		private int $electionDuration,
		#[Autowire('%politics_mandate_end_duration%')]
		private int $mandateEndDuration,
		#[Autowire('%politics_putsch_duration%')]
		private int $putschDuration,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
		#[Autowire('%server_start_time%')]
		private string $serverStartTime,
	) {
	}

	public function getDateUntil(Color $faction, MandateState $mandateState, bool $mustBePresent = true): \DateTimeImmutable
	{
		if (!$this->supports($faction, $mandateState)) {
			throw new \RuntimeException(sprintf('This faction with %s regime does not support %s mandate state', $faction->regime, $mandateState->value));
		}

		$currentMandate = $this->mandateRepository->getCurrentMandate($faction)
			?? throw new \RuntimeException(sprintf('Faction %s does not have a current mandate', $faction->identifier));
		$lastEvent = $this->politicalEventRepository->getFactionLastPoliticalEvent($faction);
		$now = $this->clock->now();

		$date = $this->getDurationUntilMandateState($faction, $mandateState, $currentMandate->startedAt, $lastEvent);

		return ($mustBePresent) ? max($date, $now) : $date;
	}

	public function getDurationUntilMandateState(
		Color $faction,
		MandateState $mandateState,
		\DateTimeImmutable $from,
		PoliticalEvent|null $lastEvent = null,
	): \DateTimeImmutable {
		return $this->durationHandler->getDurationEnd(
			$from,
			match ($mandateState) {
				MandateState::DemocraticCampaign,
				MandateState::TheocraticCampaign => $this->getMandateDuration($faction),
				MandateState::DemocraticVote => $this->getMandateDuration($faction)
					+ $this->getCampaignDuration(),
				MandateState::Active => match ($faction->regime) {
					Color::REGIME_DEMOCRATIC => $this->getMandateDuration($faction)
						+ $this->getCampaignDuration()
						+ $this->getElectionDuration()
						+ $this->getMandateEndDuration(),
					Color::REGIME_THEOCRATIC => $this->getMandateDuration($faction)
						+ $this->getCampaignDuration()
						+ $this->getMandateEndDuration(),
					Color::REGIME_ROYALISTIC => (null !== $lastEvent && $lastEvent->endedAt > $now)
						? $this->durationHandler->getDiff($now, $lastEvent->endedAt)
						: 0,
				},
			},
		);
	}

	private function supports(Color $faction, MandateState $mandateState): bool
	{
		return in_array($mandateState, [MandateState::Active, ...match ($faction->regime) {
			Color::REGIME_DEMOCRATIC => [MandateState::DemocraticCampaign, MandateState::DemocraticVote],
			Color::REGIME_THEOCRATIC => [MandateState::TheocraticCampaign],
			Color::REGIME_ROYALISTIC => [],
		}]);
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

	/**
	 * @return int Duration in seconds between the start of the mandate and the start of the campaign.
	 */
	public function getMandateDuration(Color $faction): int
	{
		return $this->timeMode->isStandard() ?
			($this->getFactionsConfiguration)($faction, 'mandateDuration')
			: 60 * 2;
	}

	private function calculate(Color $faction, int $duration = 0, bool $addMandateDuration = true): \DateTimeImmutable
	{
		$lastElection = $this->politicalEventRepository->getFactionLastPoliticalEvent($faction);
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

	public function getElectionDuration(): int
	{
		return $this->timeMode->isStandard() ? $this->electionDuration : 60 * 2;
	}

	public function getCampaignDuration(): int
	{
		return $this->timeMode->isStandard() ? $this->campaignDuration : 60 * 2;
	}

	public function getMandateEndDuration(): int
	{
		return $this->timeMode->isStandard() ? $this->mandateEndDuration : 30;
	}

	public function getPutschDuration(): int
	{
		return $this->timeMode->isStandard() ? $this->putschDuration : 1200;
	}
}
