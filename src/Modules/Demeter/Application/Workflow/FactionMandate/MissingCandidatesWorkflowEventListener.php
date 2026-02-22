<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Modules\Demeter\Domain\Event\MissingCandidatesEvent;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\Attribute\AsEnteredListener;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\EnterEvent;

readonly class MissingCandidatesWorkflowEventListener
{
	public function __construct(
		private ConversationRepositoryInterface $factionConversationRepository,
		private EventDispatcherInterface $eventDispatcher,
		private LoggerInterface $logger,
		private NotificationRepositoryInterface $notificationRepository,
		private PlayerRepositoryInterface $playerRepository,
		private PoliticalEventRepositoryInterface $politicalEventRepository,
		private GetFactionsConfiguration $getFactionsConfiguration,
	) {
	}

	#[AsEnterListener(workflow: 'faction_mandate', place: MandateState::Active->value)]
	public function onMissingCandidates(EnterEvent $event): void
	{
		if ('missing_candidates' !== $event->getTransition()->getName()) {
			return;
		}

		/** @var Color $faction */
		$faction = $event->getSubject();

		$previousLeader = $this->playerRepository->getFactionLeader($faction);

		if (null !== $previousLeader && Color::REGIME_DEMOCRATIC === $faction->regime) {
			$this->notificationRepository->save(NotificationBuilder::new()
				->setTitle('Vous demeurez '.($this->getFactionsConfiguration)($faction, 'status')[Player::CHIEF - 1])
				->setContent(NotificationBuilder::paragraph(
					'Aucun candidat ne s\'est présenté oour vous remplacer lors des dernières élections.',
					'Par conséquent, vous êtes toujours à la tête de ',
					($this->getFactionsConfiguration)($faction, 'popularName'),
				))
				->for($previousLeader));
		} elseif (null !== $previousLeader && Color::REGIME_THEOCRATIC === $faction->regime) {
			$this->notificationRepository->save(NotificationBuilder::new()
				->setTitle('Vous avez été nommé Guide')
				->setContent(NotificationBuilder::paragraph(
					'Les Oracles ont parlé,',
					' vous êtes toujours désigné par la Grande Lumière pour guider Cardan vers la Gloire.',
				))
				->for($previousLeader));
		}

		$factionAccount = $this->playerRepository->getFactionAccount($faction)
			?? throw new \LogicException(sprintf('Faction %d account not found', $faction->identifier));
		$factionConversation = $this->factionConversationRepository->getOneByPlayer($factionAccount);

		$this->eventDispatcher->dispatch(new MissingCandidatesEvent(
			factionName: ($this->getFactionsConfiguration)($faction, 'popularName'),
			factionAccount: $factionAccount,
			politicalEvent: $this->politicalEventRepository->getFactionLastPoliticalEvent($faction),
			factionConversation: $factionConversation,
			regime: $faction->regime,
			currentLeader: $previousLeader,
		));
	}

	#[AsEnteredListener(workflow: 'faction_mandate', place: MandateState::Active->value)]
	public function onMissingCandidatesEntered(EnteredEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		if ('missing_candidates' !== $event->getTransition()->getName()) {
			$this->logger->debug('Faction {factionIdentifier} entered {state} state through {transition} transition, skipping MissingCandidatesEntered event listener.', [
				'factionIdentifier' => $faction->identifier,
				'state' => MandateState::Active->value,
				'transition' => $event->getTransition()->getName(),
			]);

			return;
		}

		$this->logger->debug('Faction {factionIdentifier} entered {state} state through {transition} transition, executing MissingCandidatesEntered event listener.', [
			'factionIdentifier' => $faction->identifier,
			'state' => MandateState::Active->value,
			'transition' => $event->getTransition()->getName(),
		]);
	}
}
