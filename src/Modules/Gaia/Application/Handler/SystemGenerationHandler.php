<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Application\Handler;

use App\Modules\Gaia\Application\Message\PlaceGenerationMessage;
use App\Modules\Gaia\Application\Message\SystemGenerationMessage;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Gaia\Domain\Enum\SystemType;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SystemRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class SystemGenerationHandler
{
	public function __construct(
		private MessageBusInterface $messageBus,
		private SectorRepositoryInterface $sectorRepository,
		private SystemRepositoryInterface $systemRepository,
	) {
	}

	public function __invoke(SystemGenerationMessage $message): void
	{
		$sector = $this->sectorRepository->getOneByIdentifier($message->sectorIdentifier)
			?? throw new \LogicException('Sector not found');

		$system = new System(
			id: Uuid::v4(),
			sector: $sector,
			faction: null,
			xPosition: $message->xPosition,
			yPosition: $message->yPosition,
			typeOfSystem: SystemType::from($message->typeOfSystem),
		);

		$this->systemRepository->save($system);

		$placesCount = $this->getPlacesCount($system->typeOfSystem);

		for ($position = 1; $position <= $placesCount; $position++) {
			$this->messageBus->dispatch(new PlaceGenerationMessage(
				systemId: $system->id,
				position: $position,
				sectorDanger: $message->sectorDanger,
			));
		}
	}

	protected function getPlacesCount(SystemType $systemType): int
	{
		$nbrPlaces = $systemType->getPlacesCountRange();

		return random_int($nbrPlaces[0], $nbrPlaces[1]);
	}
}
