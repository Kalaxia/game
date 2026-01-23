<?php

namespace App\Modules\Demeter\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Message\ElectionMessage;
use App\Modules\Demeter\Message\SenateUpdateMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsParliamentMember;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\SchedulerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class ColorManager implements SchedulerInterface
{
	public function __construct(
		private ColorRepositoryInterface        $colorRepository,
		private GetFactionsConfiguration 		$getFactionsConfiguration,
		private PlayerRepositoryInterface       $playerRepository,
		private NotificationRepositoryInterface $notificationRepository,
		private MessageBusInterface             $messageBus,
		private UrlGeneratorInterface           $urlGenerator,
		private EntityManagerInterface          $entityManager,
		private NextElectionDateCalculator      $nextElectionDateCalculator,
	) {
	}

	public function schedule(): void
	{
		$this->scheduleSenateUpdate();
		$this->scheduleElections();
		$this->scheduleCampaigns();
		$this->scheduleBallot();
	}

	public function scheduleSenateUpdate(): void
	{
		$factions = $this->colorRepository->getByRegimesAndMandateStates([Color::REGIME_ROYALISTIC], [MandateState::Active]);

		foreach ($factions as $faction) {
			$this->messageBus->dispatch(
				new SenateUpdateMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getSenateUpdateMessage($faction))],
			);
		}
	}

	public function scheduleCampaigns(): void
	{
		$factions = $this->colorRepository->getByRegimesAndMandateStates(
			[Color::REGIME_DEMOCRATIC, Color::REGIME_THEOCRATIC],
			[MandateState::Active]
		);

		foreach ($factions as $faction) {
			$this->messageBus->dispatch(
				new CampaignMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getCampaignStartDate($faction))],
			);
		}
		$factions = $this->colorRepository->getByRegimesAndMandateStates(
			[Color::REGIME_ROYALISTIC],
			[MandateState::Putsch],
		);
		foreach ($factions as $faction) {
			$this->messageBus->dispatch(
				new BallotMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getPutschEndDate($faction))],
			);
		}
	}

	public function scheduleElections(): void
	{
		$factions = $this->colorRepository->getByRegimesAndMandateStates(
			[Color::REGIME_DEMOCRATIC],
			[MandateState::DemocraticCampaign],
		);
		foreach ($factions as $faction) {
			$this->messageBus->dispatch(
				new ElectionMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getNextElectionDate($faction))],
			);
		}
	}

	public function scheduleBallot(): void
	{
		$factions = array_merge(
			$this->colorRepository->getByRegimesAndMandateStates(
				[Color::REGIME_DEMOCRATIC],
				[MandateState::DemocraticVote],
			),
			$this->colorRepository->getByRegimesAndMandateStates(
				[Color::REGIME_THEOCRATIC],
				[MandateState::TheocraticCampaign],
			)
		);
		foreach ($factions as $faction) {
			$this->messageBus->dispatch(
				new BallotMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getBallotDate($faction))],
			);
		}
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
					($this->getFactionsConfiguration)($faction, 'status')[5]
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
