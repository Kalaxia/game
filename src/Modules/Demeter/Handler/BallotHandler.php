<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Handler;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Color;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
readonly class BallotHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private CandidateRepositoryInterface $candidateRepository,
		private EntityManagerInterface $entityManager,
		private LoggerInterface $logger,
		private PoliticalEventRepositoryInterface $electionRepository,
		private WorkflowInterface $factionMandateStateMachine,
	) {
	}

	public function __invoke(BallotMessage $message): void
	{
		$faction = $this->colorRepository->get($message->factionId)
			?? throw new \RuntimeException(sprintf('Faction %s not found', $message->factionId));

		$election = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$candidates = $this->candidateRepository->getByPoliticalEvent($election);
		$candidatesCount = count($candidates);

		$this->logger->debug('Applying ballot transition for faction {factionName} with regime {regime} and {candidatesCount} candidates', [
			'factionName' => $faction->identifier,
			'regime' => $faction->regime,
			'candidatesCount' => $candidatesCount,
		]);

		if (0 === $candidatesCount && in_array($faction->regime, [Color::REGIME_DEMOCRATIC, Color::REGIME_THEOCRATIC])) {
			$this->factionMandateStateMachine->apply($faction, 'missing_candidates');

			return;
		}

		if (Color::REGIME_ROYALISTIC === $faction->regime) {
			$this->factionMandateStateMachine->apply($faction, 'royalistic_crowning');

			return;
		}

		$this->factionMandateStateMachine->apply($faction, match ($faction->regime) {
			Color::REGIME_DEMOCRATIC => 'democratic_result',
			Color::REGIME_THEOCRATIC => 'theocratic_designation',
			default => throw new \RuntimeException(sprintf('Faction %s has a regime without vote result', $faction->identifier)),
		});

		$this->entityManager->flush();
	}
}
