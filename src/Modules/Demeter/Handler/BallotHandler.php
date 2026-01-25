<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Handler;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\PoliticalEventRepositoryInterface;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Color;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
readonly class BallotHandler
{
	public function __construct(
		private ColorRepositoryInterface          $colorRepository,
		private CandidateRepositoryInterface      $candidateRepository,
		private PoliticalEventRepositoryInterface $electionRepository,
		private WorkflowInterface                 $factionMandateWorkflow,
	) {
	}

	public function __invoke(BallotMessage $message): void
	{
		$faction = $this->colorRepository->get($message->factionId)
			?? throw new \RuntimeException(sprintf(
				'Faction %s not found',
				$message->factionId,
			));

		$election = $this->electionRepository->getFactionLastPoliticalEvent($faction);
		$candidates = $this->candidateRepository->getByPoliticalEvent($election);
		$candidatesCount = count($candidates);

		if (0 === $candidatesCount && in_array($faction->regime, [Color::REGIME_DEMOCRATIC, Color::REGIME_THEOCRATIC])) {
			$this->factionMandateWorkflow->apply($faction, 'missing_candidates');

			return;
		}

		if (Color::REGIME_ROYALISTIC === $faction->regime) {
			$this->factionMandateWorkflow->apply($faction, 'royalistic_crowning');

			return;
		}

		$this->factionMandateWorkflow->apply($faction, match ($faction->regime) {
			Color::REGIME_DEMOCRATIC => 'democratic_result',
			Color::REGIME_THEOCRATIC => 'theocratic_designation',
			default => throw new \RuntimeException(sprintf(
				'Faction %s has a regime without vote result',
				$faction->identifier,
			)),
		});
	}
}
