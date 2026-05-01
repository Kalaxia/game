<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Manager;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\MandateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Message\ElectionMessage;
use App\Modules\Demeter\Message\MandateExpirationMessage;
use App\Modules\Demeter\Message\SenateUpdateMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Application\Persister\NotificationPersister;
use App\Modules\Shared\Infrastructure\Messenger\ScheduleTask;
use App\Modules\Zeus\Infrastructure\Validator\IsParliamentMember;
use App\Shared\Application\Handler\DurationHandler;
use App\Shared\Application\SchedulerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class ColorManager implements SchedulerInterface
{
	public function __construct(
		private DurationHandler $durationHandler,
		private ColorRepositoryInterface $colorRepository,
		private GetFactionsConfiguration $getFactionsConfiguration,
		private MandateRepositoryInterface $mandateRepository,
		private PoliticalEventRepositoryInterface $politicalEventRepository,
		private NotificationPersister $notificationPersister,
		private UrlGeneratorInterface $urlGenerator,
		private NextElectionDateCalculator $nextElectionDateCalculator,
		private ScheduleTask $scheduleTask,
		#[Autowire('%server_start_time%')]
		private string $serverStartTime,
	) {
	}

	public function schedule(): void
	{
		foreach ($this->colorRepository->getInGameFactions() as $faction) {
			$this->scheduleFactionPoliticalEvents($faction);
			$this->scheduleSenateUpdate($faction);
			$this->scheduleMandateExpiration($faction);
		}
	}

	private function scheduleFactionPoliticalEvents(Color $faction): void
	{
		$lastEvent = $this->politicalEventRepository->getFactionLastPoliticalEvent($faction);

		if (null === $lastEvent) {
			$this->scheduleFirstEvent($faction);

			return;
		}

		if (MandateState::Active === $faction->mandateState && !$faction->isRoyalistic()) {
			$nextCampaignStartedAt = $this->durationHandler->getDurationEnd(
				$lastEvent->endedAt,
				$this->nextElectionDateCalculator->getMandateDuration($faction),
			);
			($this->scheduleTask)(
				message: new CampaignMessage($faction->id, $nextCampaignStartedAt),
				datetime: $nextCampaignStartedAt,
			);

			return;
		}

		match ($faction->mandateState) {
			MandateState::DemocraticCampaign => ($this->scheduleTask)(
				message: new ElectionMessage($faction->id),
				datetime: $lastEvent->campaignEndedAt,
			),
			MandateState::DemocraticVote,
			MandateState::TheocraticCampaign,
			MandateState::Putsch => ($this->scheduleTask)(
				message: new BallotMessage($faction->id),
				datetime: $lastEvent->endedAt,
			),
		};
	}

	private function scheduleFirstEvent(Color $faction): void
	{
		if ($faction->isRoyalistic()) {
			return;
		}

		$campaignStartedAt = $this->durationHandler->getDurationEnd(
			new \DateTimeImmutable($this->serverStartTime),
			$this->nextElectionDateCalculator->getCampaignDuration(),
		);

		($this->scheduleTask)(
			message: new CampaignMessage($faction->id, $campaignStartedAt),
			datetime: $campaignStartedAt,
		);
	}

	public function scheduleSenateUpdate(Color $faction): void
	{
		if (!$faction->isRoyalistic()) {
			return;
		}

		($this->scheduleTask)(
			message: new SenateUpdateMessage($faction->id),
			datetime: $this->nextElectionDateCalculator->getSenateUpdateMessage($faction),
		);
	}

	private function scheduleMandateExpiration(Color $faction): void
	{
		if ($faction->isRoyalistic()) {
			return;
		}

		$currentMandate = $this->mandateRepository->getCurrentMandate($faction)
			?? $this->mandateRepository->getLastMandate($faction);

		if (null === $currentMandate) {
			return;
		}

		($this->scheduleTask)(
			message: new MandateExpirationMessage($currentMandate->id),
			datetime: $currentMandate->expiredAt,
		);
	}

	public function sendSenateNotif(Color $faction, bool $isFromChief = false): void
	{
		$this->notificationPersister->saveFromBuilder(
			NotificationBuilder::new()
				->setTitle($isFromChief ? 'Loi appliquée' : 'Loi proposée')
				->setContent(NotificationBuilder::paragraph(
					$isFromChief
						? sprintf(
						'Votre %s a appliqué une loi.',
						($this->getFactionsConfiguration)($faction, 'status')[5],
					)
						: 'Votre gouvernement a proposé un projet de loi, en tant que membre du sénat,
						il est de votre devoir de voter pour l\'acceptation ou non de ladite loi.',
					NotificationBuilder::divider(),
					NotificationBuilder::link(
						$this->urlGenerator->generate('faction_senate'),
						$isFromChief ? 'voir les lois appliquées' : 'voir les lois en cours de vote',
					),
				))
				->withRecipientSpecification(new IsParliamentMember($faction))
		);
	}
}
