<?php

namespace App\Modules\Demeter\Handler;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Message\SenateUpdateMessage;
use App\Modules\Demeter\Model\Color;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class SenateUpdateHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private ColorManager $colorManager,
		private MessageBusInterface $messageBus,
		private NextElectionDateCalculator $nextElectionDateCalculator,
	) {
	}

	public function __invoke(SenateUpdateMessage $message): void
	{
		$faction = $this->colorRepository->get($message->getFactionId());
		$this->colorManager->updateSenate($faction);

		if ($faction->isRoyalistic() && Color::MANDATE === $faction->isInMandate()) {
			$date = $this->nextElectionDateCalculator->getSenateUpdateMessage($faction);
			$faction->lastElectionHeldAt = $date;

			$this->messageBus->dispatch(
				new SenateUpdateMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($date)],
			);
			$this->colorRepository->save($faction);
		}
	}
}
