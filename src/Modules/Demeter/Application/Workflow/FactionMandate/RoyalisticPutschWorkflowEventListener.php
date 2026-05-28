<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Modules\Demeter\Domain\Event\NewPutschAttemptEvent;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Demeter\Model\Election\Putsch;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Event\EnterEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

readonly class RoyalisticPutschWorkflowEventListener
{
	public function __construct(
		private CandidateRepositoryInterface $candidateRepository,
		private EventDispatcherInterface $eventDispatcher,
		private PoliticalEventRepositoryInterface $politicalEventRepository,
		private PlayerRepositoryInterface $playerRepository,
	) {
	}

	#[AsGuardListener(workflow: 'faction_mandate', transition: 'royalistic_putsch')]
	public function guard(GuardEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		if (Color::REGIME_ROYALISTIC !== $faction->regime) {
			$event->setBlocked(true, sprintf('Faction %s is not royalistic', $faction->identifier));
		}

		if (MandateState::Active !== $faction->mandateState) {
			$event->setBlocked(true, sprintf('There already is an ongoing coup in faction %s', $faction->identifier));
		}
	}

	#[AsEnterListener(workflow: 'faction_mandate', place: MandateState::Putsch->value)]
	public function onPutschStart(EnterEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		/** @var Putsch $putsch */
		$putsch = $this->politicalEventRepository->getFactionLastPoliticalEvent($faction);

		$factionLeader = $this->playerRepository->getFactionLeader($faction);

		$candidate = $this->candidateRepository->getByPoliticalEvent($putsch)[0]
			?? throw new \RuntimeException('Missing putsch candidate');

		$this->eventDispatcher->dispatch(new NewPutschAttemptEvent($putsch, $candidate, $factionLeader));
	}
}
