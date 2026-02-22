<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Application\Handler;

use App\Modules\Galaxy\Application\Message\PlanetsUpdateMessage;
use App\Modules\Galaxy\Application\Message\PlanetUpdateMessage;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class PlanetsUpdateHandler
{
	public function __construct(
		private MessageBusInterface $messageBus,
		private PlanetRepositoryInterface $planetRepository,
	) {
	}

	public function __invoke(PlanetsUpdateMessage $message): void
	{
		$bases = $this->planetRepository->getAll();

		foreach ($bases as $base) {
			$this->messageBus->dispatch(new PlanetUpdateMessage($base->id));
		}
	}
}
