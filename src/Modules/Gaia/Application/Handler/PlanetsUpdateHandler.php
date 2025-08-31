<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Application\Handler;

use App\Modules\Gaia\Application\Message\PlanetsUpdateMessage;
use App\Modules\Gaia\Application\Message\PlanetUpdateMessage;
use App\Modules\Gaia\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsPlayerAlive;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class PlanetsUpdateHandler
{
	public function __construct(
		private MessageBusInterface       $messageBus,
		private PlanetRepositoryInterface $planetRepository,
	) {
	}

	public function __invoke(PlanetsUpdateMessage $message): void
	{
		$bases = $this->planetRepository->getBySpecification(new IsPlayerAlive());

		foreach ($bases as $base) {
			$this->messageBus->dispatch(new PlanetUpdateMessage($base->id));
		}
	}
}
