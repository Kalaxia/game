<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Domain\Service\UpdateSenate;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Message\ElectionMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\PoliticalEvent;
use App\Modules\Demeter\Model\Election\MandateState;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\Attribute\AsEnteredListener;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Event\Event;

readonly class TheocraticCampaignWorkflowEventListener
{
	public function __construct(
		private PoliticalEventRepositoryInterface $electionRepository,
		private NextElectionDateCalculator        $nextElectionDateCalculator,
		private MessageBusInterface               $messageBus,
		private UpdateSenate                      $updateSenate,
	) {
	}

	#[AsGuardListener(workflow: 'faction_mandate', transition: 'to_campaign')]
	public function guard(Event $event): void
	{

	}

	#[AsEnterListener(workflow: 'faction_mandate', place: MandateState::DemocraticCampaign->value)]
	public function onEnter(Event $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		($this->updateSenate)($faction);
	}

	#[AsEnteredListener(workflow: 'faction_mandate', place: MandateState::DemocraticCampaign->value)]
	public function onEntered(Event $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		$election = new PoliticalEvent(
			id: Uuid::v4(),
			faction: $faction,
			startedAt: $this->nextElectionDateCalculator->getCampaignEndDate($faction),
		);

		$this->electionRepository->save($election);

		$this->messageBus->dispatch(
			new BallotMessage($faction->id),
			[DateTimeConverter::to_delay_stamp($election->startedAt)],
		);
	}
}
