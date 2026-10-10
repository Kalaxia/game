<?php

declare(strict_types=1);

namespace App\Tests\Modules\Ares\Support;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Entity\Sector;
use App\Modules\Galaxy\Domain\Entity\System;
use App\Modules\Galaxy\Domain\Enum\PlanetType;
use App\Modules\Galaxy\Domain\Enum\SystemType;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

final class BuildPlanet
{
	public static function build(
		int $danger = 0,
		int $maxDanger = 10,
		int $population = 100,
		int $position = 1,
		int $historyCoeff = 20,
		int $resourcesCoeff = 60,
		?Player $player = null,
	): Planet {
		return new Planet(
			id: Uuid::v4(),
			system: new System(
				id: Uuid::v4(),
				sector: new Sector(
					id: Uuid::v4(),
					identifier: 1,
					faction: null,
					xPosition: 10,
					yPosition: 10,
					xBarycentric: 0,
					yBarycentric: 0,
					tax: 5,
					name: null,
					points: 5,
					population: 0,
					prime: true,
				),
				faction: null,
				xPosition: 10,
				yPosition: 10,
				typeOfSystem: SystemType::BlueGiant,
			),
			position: $position,
			planetType: PlanetType::Desert,
			player: $player,
			population: $population,
			coefResources: $resourcesCoeff,
			coefHistory: $historyCoeff,
			danger: $danger,
			maxDanger: $maxDanger,
			updatedAt: new \DateTimeImmutable(),
		);
	}
}
