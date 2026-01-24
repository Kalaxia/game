<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Handler;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Message\ElectionMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
readonly class ElectionHandler
{
	public function __construct(
		private ColorRepositoryInterface          $colorRepository,
		private CandidateRepositoryInterface      $candidateRepository,
		private PoliticalEventRepositoryInterface $electionRepository,
		private WorkflowInterface                 $factionMandateWorkflow,
	) {
	}

	public function __invoke(ElectionMessage $message): void
	{
		$faction = $this->colorRepository->get($message->getFactionId())
			?? throw new \RuntimeException(sprintf('Faction %s not found', $message->getFactionId()));

		$election = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$candidates = $this->candidateRepository->getByPoliticalEvent($election);

		$this->factionMandateWorkflow->apply($faction, match (count($candidates)) {
			0 => 'missing_candidates',
			1 => 'unique_candidate',
			default => 'democratic_vote',
		});
	}
}
