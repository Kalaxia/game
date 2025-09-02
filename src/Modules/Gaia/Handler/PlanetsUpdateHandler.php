<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Handler;

use App\Modules\Gaia\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Gaia\Message\PlanetUpdateMessage;
use App\Modules\Gaia\Message\PlanetsUpdateMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class PlanetsUpdateHandler
{
	public function __construct(
		private PlanetRepositoryInterface $planetRepository,
		private MessageBusInterface      $messageBus,
	) {
	}

	public function __invoke(PlanetsUpdateMessage $message): void
	{
		foreach ($this->planetRepository->getAll() as $place) {
			$this->messageBus->dispatch(new PlanetUpdateMessage($place->id));
		}
	}
}
