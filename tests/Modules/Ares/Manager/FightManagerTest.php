<?php

namespace App\Tests\Modules\Ares\Manager;

use App\Modules\Ares\Application\Factory\ReportFactory;
use App\Modules\Ares\Manager\FightManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\LiveReport;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Gaia\Domain\Entity\Place;
use App\Modules\Gaia\Domain\Entity\Sector;
use App\Modules\Gaia\Domain\Entity\System;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

class FightManagerTest extends KernelTestCase
{
	public function testStartFight(): void
	{
		static::bootKernel();

		static::markTestIncomplete('Must implement Foundry');

		/** @var FightManager $fightManager */
		$fightManager = static::getContainer()->get(FightManager::class);
		/** @var ReportFactory $reportFactory */
		$reportFactory = static::getContainer()->get(ReportFactory::class);

		$commanderA = $this->getCommanderMock('attacker', 1);
		$commanderD = $this->getCommanderMock('defender', 1);

		$fightManager->startFight(
			$commanderA,
			$commanderD,
		);

		LiveReport::$isLegal = true;
		LiveReport::$dFight = new \DateTimeImmutable();

		$report = $reportFactory->create($this->getPlaceMock());

		dump($report);
	}

	private function getCommanderMock(string $name, int $squadronsCount): Commander
	{
		$player = new Player();
		$player->victory = 5;
		$player->defeat = 1;

		$commander = new Commander(
			id: Uuid::v4(),
			name: $name,
			avatar: 't3-c4',
			player: $player,
			base: null,
			enlistedAt: new \DateTimeImmutable(),
			level: 1,
			updatedAt: new \DateTimeImmutable(),
			isVirtual: true,
		);

		for ($i = 0; $i < $squadronsCount; $i++) {
			$commander->squadrons->add($this->getSquadronMock($commander));
		}

		return $commander;
	}

	private function getSquadronMock(Commander $commander): Squadron
	{
		return new Squadron(
			id: Uuid::v4(),
			commander: $commander,
			createdAt: new \DateTimeImmutable(),
			updatedAt: new \DateTimeImmutable(),
			ship0: 4,
			ship1: 1,
		);
	}

	private function getPlaceMock(): Place
	{
		return new Place(
			id: Uuid::v4(),
			player: null,
			base: null,
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
					lifePlanet: 10,
					prime: 1,
				),
				faction: null,
				xPosition: 10,
				yPosition: 10,
				typeOfSystem: 0,
			),
			typeOfPlace: Place::TERRESTRIAL,
			position: 1,
			population: 100,
			coefResources: 60,
			coefHistory: 20,
			resources: 0,
			danger: 5,
			maxDanger: 10,
			updatedAt: new \DateTimeImmutable(),
		);
	}
}
