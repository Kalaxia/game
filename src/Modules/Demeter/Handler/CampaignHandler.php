<?php

namespace App\Modules\Demeter\Handler;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Model\Color;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
readonly class CampaignHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private WorkflowInterface $factionMandateWorkflow,
	) {
	}

	public function __invoke(CampaignMessage $message): void
	{
		$faction = $this->colorRepository->get($message->getFactionId())
			?? throw new \RuntimeException(sprintf(
				'Faction %s not found',
				$message->getFactionId(),
			));

		$this->factionMandateWorkflow->apply($faction, match ($faction->regime) {
			Color::REGIME_DEMOCRATIC => 'democratic_campaign',
			Color::REGIME_THEOCRATIC => 'theocratic_campaign',
			default => throw new \RuntimeException(sprintf(
				'Faction %s has a regime without campaigns',
				$faction->identifier,
			)),
		});
	}
}
