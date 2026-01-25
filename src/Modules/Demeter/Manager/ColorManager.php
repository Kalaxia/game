<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Message\ElectionMessage;
use App\Modules\Demeter\Message\SenateUpdateMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsParliamentMember;
use App\Shared\Application\Handler\DurationHandler;
use App\Shared\Application\SchedulerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class ColorManager implements SchedulerInterface
{
	public function __construct(
		private DurationHandler $durationHandler,
		private ColorRepositoryInterface        $colorRepository,
		private GetFactionsConfiguration 		$getFactionsConfiguration,
		private PlayerRepositoryInterface       $playerRepository,
		private PoliticalEventRepositoryInterface $politicalEventRepository,
		private NotificationRepositoryInterface $notificationRepository,
		private MessageBusInterface             $messageBus,
		private UrlGeneratorInterface           $urlGenerator,
		private NextElectionDateCalculator      $nextElectionDateCalculator,
		#[Autowire('%server_start_time%')]
		private string $serverStartTime,
	) {
	}

	public function schedule(): void
	{
		foreach ($this->colorRepository->getAll() as $faction) {
			$this->scheduleFactionPoliticalEvents($faction);
			$this->scheduleSenateUpdate($faction);
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
			$this->messageBus->dispatch(
				new CampaignMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($this->durationHandler->getDurationEnd(
					$lastEvent->endedAt,
					$this->nextElectionDateCalculator->getMandateDuration($faction),
				))],
			);

			return;
		}

		match ($faction->mandateState) {
			MandateState::DemocraticCampaign => $this->messageBus->dispatch(
				new ElectionMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($lastEvent->campaignEndedAt)],
			),
			MandateState::DemocraticVote,
			MandateState::TheocraticCampaign,
			MandateState::Putsch => $this->messageBus->dispatch(
				new BallotMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($lastEvent->endedAt)],
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

		$this->messageBus->dispatch(
			new CampaignMessage($faction->id),
			[DateTimeConverter::to_delay_stamp($campaignStartedAt)],
		);
	}

	public function scheduleSenateUpdate(Color $faction): void
	{
		if (!$faction->isRoyalistic()) {
			return;
		}

		$this->messageBus->dispatch(
			new SenateUpdateMessage($faction->id),
			[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getSenateUpdateMessage($faction))],
		);
	}

	public function sendSenateNotif(Color $faction, bool $isFromChief = false): void
	{
		$parliamentMembers = $this->playerRepository->getBySpecification(new IsParliamentMember($faction));

		$notificationBuilder = NotificationBuilder::new()
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
			));

		foreach ($parliamentMembers as $parliamentMember) {
			$this->notificationRepository->save($notificationBuilder->for($parliamentMember));
		}
	}
}
