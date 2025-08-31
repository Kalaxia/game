<?php

namespace App\Modules\Zeus\Application\EventListener;

use App\Modules\Ares\Domain\Event\Commander\AffectationEvent;
use App\Modules\Ares\Domain\Event\Commander\LineChangeEvent;
use App\Modules\Ares\Domain\Event\Commander\NewCommanderEvent;
use App\Modules\Ares\Domain\Event\Fleet\PlannedLootEvent;
use App\Modules\Ares\Domain\Event\Fleet\SquadronUpdateEvent;
use App\Modules\Artemis\Domain\Event\SpyEvent;
use App\Modules\Athena\Domain\Event\NewBuildingQueueEvent;
use App\Modules\Athena\Domain\Event\NewShipQueueEvent;
use App\Modules\Gaia\Resource\PlanetResource;
use App\Modules\Promethee\Domain\Event\NewTechnologyQueueEvent;
use App\Modules\Zeus\Domain\Event\UniversityInvestmentsUpdateEvent;
use App\Modules\Zeus\Helper\TutorialHelper;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TutorialEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class TutorialStepValidationListener
{
	public function __construct(
		private readonly TutorialHelper $tutorialHelper,
	) {
	}

	#[AsEventListener]
	public function onNewBuildingQueue(NewBuildingQueueEvent $event): void
	{
		$player = $event->buildingQueue->base->player;
		$buildingQueue = $event->buildingQueue;
		$targetLevel = $buildingQueue->targetLevel;

		$data = match ($buildingQueue->buildingNumber) {
			PlanetResource::GENERATOR => [
				TutorialResource::GENERATOR_LEVEL_2 => 2,
			],
			PlanetResource::DOCK1 => [
				TutorialResource::DOCK1_LEVEL_1 => 1,
				TutorialResource::DOCK1_LEVEL_6 => 6,
				TutorialResource::DOCK1_LEVEL_15 => 15,
			],
			PlanetResource::REFINERY => [
				TutorialResource::REFINERY_LEVEL_3 => 3,
				TutorialResource::REFINERY_LEVEL_10 => 10,
				TutorialResource::REFINERY_LEVEL_16 => 16,
				TutorialResource::REFINERY_LEVEL_20 => 20,
			],
			PlanetResource::STORAGE => [
				TutorialResource::STORAGE_LEVEL_3 => 3,
				TutorialResource::STORAGE_LEVEL_8 => 8,
				TutorialResource::STORAGE_LEVEL_12 => 12,
			],
			PlanetResource::TECHNOSPHERE => [
				TutorialResource::TECHNOSPHERE_LEVEL_1 => 1,
				TutorialResource::TECHNOSPHERE_LEVEL_6 => 6,
			],
			default => [],
		};

		foreach ($data as $tutorialStep => $buildingNeededLevel) {
			if ($tutorialStep === $player->stepTutorial && $targetLevel >= $buildingNeededLevel) {
				$this->tutorialHelper->setStepDone($player);

				break;
			}
		}
	}

	#[AsEventListener(AffectationEvent::class)]
	#[AsEventListener(LineChangeEvent::class)]
	#[AsEventListener(PlannedLootEvent::class)]
	#[AsEventListener(NewCommanderEvent::class)]
	#[AsEventListener(NewShipQueueEvent::class)]
	#[AsEventListener(NewTechnologyQueueEvent::class)]
	#[AsEventListener(UniversityInvestmentsUpdateEvent::class)]
	#[AsEventListener(SpyEvent::class)]
	#[AsEventListener(SquadronUpdateEvent::class)]
	public function validateStep(TutorialEvent $event): void
	{
		$player = $event->getTutorialPlayer();

		if (!$player->stepDone && $player->stepTutorial === $event->getTutorialStep()) {
			$this->tutorialHelper->setStepDone($player);
		}
	}
}
