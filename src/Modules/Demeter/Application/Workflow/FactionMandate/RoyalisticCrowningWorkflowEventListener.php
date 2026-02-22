<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Event\NewRoyalisticLeaderEvent;
use App\Modules\Demeter\Domain\Event\PutschFailedEvent;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Domain\Service\GetPutschSupportPercentage;
use App\Modules\Demeter\Message\SenateUpdateMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Model\Election\Putsch;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Shared\Infrastructure\Messenger\ScheduleTask;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Event\EnterEvent;

readonly class RoyalisticCrowningWorkflowEventListener
{
	public function __construct(
		private GetPutschSupportPercentage $getPutschSupportPercentage,
		private PoliticalEventRepositoryInterface $electionRepository,
		private CandidateRepositoryInterface $candidateRepository,
		private PlayerRepositoryInterface $playerRepository,
		private EventDispatcherInterface $eventDispatcher,
		private ConversationRepositoryInterface $conversationRepository,
		private GetFactionsConfiguration $getFactionsConfiguration,
		private ScheduleTask $scheduleTask,
		private NotificationRepositoryInterface $notificationRepository,
		private UrlGeneratorInterface $urlGenerator,
		private NextElectionDateCalculator $nextElectionDateCalculator,
	) {
	}

	#[AsEnterListener(workflow: 'faction_mandate', place: MandateState::Active->value)]
	public function onRoyalisticCrowning(EnterEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		if (!$faction->isRoyalistic()) {
			return;
		}
		/** @var Putsch $putsch */
		$putsch = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$putschist = $this->candidateRepository->getByPoliticalEvent($putsch)[0]?->player
			?? throw new \LogicException(sprintf('Could not find putschist of putsch %s', $putsch->id->toRfc4122()));

		$currentLeader = $this->playerRepository->getFactionLeader($faction);

		/** @var Color $faction */
		$faction = $event->getSubject();
		$putschSupportPercentage = ($this->getPutschSupportPercentage)($faction);
		if ($putschSupportPercentage >= Color::PUTSCHPERCENTAGE) {
			$this->crownNewLeader($faction, $putsch, $putschist, $currentLeader, $putschSupportPercentage);
		} else {
			$this->reinstatePreviousLeader($faction, $currentLeader, $putschist);
		}
	}

	private function crownNewLeader(
		Color $faction,
		Putsch $putsch,
		Player $newLeader,
		?Player $previousLeader,
		float $supportPercentage,
	): void {
		$statuses = ($this->getFactionsConfiguration)($faction, 'status');
		($this->scheduleTask)(
			message: new SenateUpdateMessage($faction->id),
			datetime: new \DateTimeImmutable(sprintf(
				'+%d seconds',
				$this->nextElectionDateCalculator->getSenateUpdateMessage($faction)->getTimestamp(),
			)),
		);

		if (null !== $previousLeader) {
			$this->notificationRepository->save(NotificationBuilder::new()
				->setTitle('Un coup d\'état a réussi')
				->setContent(NotificationBuilder::paragraph(
					'Le joueur ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('embassy', ['player' => $newLeader->id]),
						$newLeader->name,
					),
					' a fait un coup d\'état, vous êtes évincé du pouvoir.',
				))
				->for($previousLeader));
		}

		$factionPlayer = $this->playerRepository->getFactionAccount($faction);
		$this->eventDispatcher->dispatch(new NewRoyalisticLeaderEvent(
			faction: $faction,
			newLeader: $newLeader,
			politicalEvent: $putsch,
			factionName: ($this->getFactionsConfiguration)($faction, 'popularName'),
			factionStatuses: $statuses,
			factionPlayer: $factionPlayer,
			factionConversation: $this->conversationRepository->getOneByPlayer($factionPlayer),
			candidatesData: [
				'supportPercentage' => $supportPercentage,
			],
		));
	}

	private function reinstatePreviousLeader(Color $faction, ?Player $leader, Player $putschist): void
	{
		if (null !== $leader) {
			$this->notificationRepository->save(NotificationBuilder::new()
				->setTitle('Un coup d\'état a échoué')
				->setContent(NotificationBuilder::paragraph(
					// TODO replace "player"'s notion with a proper IG status
					' Le joueur ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('embassy', ['player' => $putschist->id]),
						$putschist->name,
					),
					' a tenté un coup d\'état, celui-ci a échoué.',
				))
				->for($leader));
		}

		$factionAccount = $this->playerRepository->getFactionAccount($faction);
		$this->eventDispatcher->dispatch(new PutschFailedEvent(
			leader: $leader,
			putchist: $putschist,
			factionAccount: $factionAccount,
			factionConversation: $this->conversationRepository->getOneByPlayer($factionAccount),
			factionName: ($this->getFactionsConfiguration)($faction, 'popularName'),
		));
	}
}
