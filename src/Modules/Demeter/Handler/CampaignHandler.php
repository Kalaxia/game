<?php

namespace App\Modules\Demeter\Handler;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Model\Color;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
readonly class CampaignHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private EntityManagerInterface $entityManager,
		private LoggerInterface $logger,
		private WorkflowInterface $factionMandateStateMachine,
	) {
	}

	public function __invoke(CampaignMessage $message): void
	{
		$faction = $this->colorRepository->get($message->factionId)
			?? throw new \RuntimeException(sprintf('Faction %s not found', $message->factionId));

		$this->logger->debug('Applying campaign transition for faction {factionName} with regime {regime}', [
			'factionName' => $faction->identifier,
			'regime' => $faction->regime,
		]);

		$this->factionMandateStateMachine->apply($faction, match ($faction->regime) {
			Color::REGIME_DEMOCRATIC => 'democratic_campaign',
			Color::REGIME_THEOCRATIC => 'theocratic_campaign',
			default => throw new \RuntimeException(sprintf('Faction %s has a regime without campaigns', $faction->identifier)),
		});

		$this->entityManager->flush();
	}
}
