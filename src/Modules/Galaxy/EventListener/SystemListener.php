<?php

namespace App\Modules\Galaxy\EventListener;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Galaxy\Domain\Event\PlanetOwnerChangeEvent;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\SystemRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class SystemListener
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private PlanetRepositoryInterface $planetRepository,
		private SystemRepositoryInterface $systemRepository,
		#[Autowire('%galaxy.scores%')]
		private array $scores,
	) {
	}

	#[AsEventListener]
	public function onPlanetOwnerChange(PlanetOwnerChangeEvent $event): void
	{
		$scores = [];
		$system = $event->planet->system;
		$planets = $this->planetRepository->getSystemPlanets($system);

		foreach ($planets as $planet) {
			$factionIdentifier = $planet->player?->faction?->identifier;

			if (null === $factionIdentifier) {
				continue;
			}

			$scores[$factionIdentifier] = ($scores[$factionIdentifier] ?? 0) + $this->scores[$planet->typeOfBase];
		}
		arsort($scores);
		$newColor = array_key_first($scores);
		$currentFactionIdentifier = $system->faction?->identifier;

		if (null === $currentFactionIdentifier || (
			$scores[$newColor] > 0
			&& $currentFactionIdentifier !== $newColor
			&& $scores[$newColor] > $scores[$currentFactionIdentifier]
		)) {
			$system->faction = $this->colorRepository->getOneByIdentifier($newColor);
		} elseif (0 === $scores[$newColor]) {
			$system->faction = null;
		}
		$this->systemRepository->save($system);
	}
}
