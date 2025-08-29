<?php

namespace App\Modules\Gaia\Helper;

use App\Modules\Gaia\Application\Message\SectorGenerationMessage;
use App\Modules\Gaia\Galaxy\GalaxyConfiguration;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class GalaxyGenerator
{
	public function __construct(
		private GalaxyConfiguration $galaxyConfiguration,
		private MessageBusInterface $messageBus,
	) {
	}

	public function generate(): void
	{
		$this->generateSectors();
	}

	public function generateSectors(): void
	{
		foreach ($this->galaxyConfiguration->sectors as $sector) {
			$this->messageBus->dispatch(new SectorGenerationMessage(
				identifier: $sector['id'],
				name: $sector['name'],
				factionIdentifier: $sector['beginColor'],
				xPosition: $sector['display'][0],
				yPosition: $sector['display'][1],
				xBarycenter: $sector['barycentre'][0],
				yBarycenter: $sector['barycentre'][1],
				danger: $sector['danger'],
				points: $sector['points'],
			));
		}
	}
}
