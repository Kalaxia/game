<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Handler;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Service\UpdateSenate;
use App\Modules\Demeter\Message\SenateUpdateMessage;
use App\Modules\Shared\Infrastructure\Messenger\ScheduleTask;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SenateUpdateHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private ScheduleTask $scheduleTask,
		private NextElectionDateCalculator $nextElectionDateCalculator,
		private UpdateSenate $updateSenate,
	) {
	}

	public function __invoke(SenateUpdateMessage $message): void
	{
		$faction = $this->colorRepository->get($message->factionId);
		($this->updateSenate)($faction);

		if ($faction->isRoyalistic() && $faction->isInMandate()) {
			($this->scheduleTask)(
				message: new SenateUpdateMessage($faction->id),
				datetime: $this->nextElectionDateCalculator->getSenateUpdateMessage($faction),
			);
		}
	}
}
