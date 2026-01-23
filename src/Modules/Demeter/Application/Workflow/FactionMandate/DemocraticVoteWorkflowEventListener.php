<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Attribute\AsEnteredListener;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Event\EnterEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

readonly class DemocraticVoteWorkflowEventListener
{
	public function __construct(
		private MessageBusInterface $messageBus,
		private NextElectionDateCalculator $nextElectionDateCalculator,
	) {
	}

	#[AsGuardListener(workflow: 'faction_mandate', transition: 'democratic_vote')]
	public function guard(GuardEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		if (!$faction->isDemocratic()) {
			$event->setBlocked(true, sprintf('Faction %s is not democratic', $faction->identifier));
		}

		if (!$faction->isInCampaign()) {
			$event->setBlocked(true, sprintf('Faction %s is not in a campaign', $faction->identifier));
		}
	}

	#[AsEnteredListener(workflow: 'faction_mandate', place: MandateState::DemocraticVote->value)]
	public function onVoteStarted(EnterEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		$this->messageBus->dispatch(
			new BallotMessage($faction->id),
			[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getStartDate($faction))]
		);
	}
}
