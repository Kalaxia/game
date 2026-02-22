<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Handler;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Message\ElectionMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
readonly class ElectionHandler
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

	public function __invoke(ElectionMessage $message): void
	{
		$faction = $this->colorRepository->get($message->factionId)
			?? throw new \RuntimeException(sprintf('Faction %s not found', $message->factionId));

		$election = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$candidates = $this->candidateRepository->getByPoliticalEvent($election);

		$this->logger->debug('Applying election transition for faction {factionName} with regime {regime} and {candidatesCount} candidates', [
			'factionName' => $faction->identifier,
			'regime' => $faction->regime,
			'candidatesCount' => count($candidates),
		]);

		$this->factionMandateStateMachine->apply($faction, match (count($candidates)) {
			0 => 'missing_candidates',
			1 => 'unique_candidate',
			default => 'democratic_vote',
		});

		$this->entityManager->flush();
	}
}
