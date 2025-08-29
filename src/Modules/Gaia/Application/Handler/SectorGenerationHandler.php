<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Application\Handler;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Gaia\Application\Message\SectorGenerationMessage;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Model\Sector;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class SectorGenerationHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private SectorRepositoryInterface $sectorRepository,
		private MessageBusInterface $messageBus,
	) {
	}

	public function __invoke(SectorGenerationMessage $message): void
	{
		$faction = (null !== $message->factionIdentifier)
			? $this->colorRepository->getOneByIdentifier($message->factionIdentifier)
				?? throw new \LogicException('Faction not found')
			: null;

		$sector = new Sector(
			id: Uuid::v4(),
			identifier: $message->identifier,
			faction: $faction,
			xPosition: $message->xPosition,
			yPosition: $message->yPosition,
			xBarycentric: $message->xBarycenter,
			yBarycentric: $message->yBarycenter,
			tax: 5,
			name: $message->name,
			points: $message->points,
			population: 0,
			lifePlanet: 0,
			prime: null !== $faction,
		);

		$this->sectorRepository->save($sector);
	}
}
