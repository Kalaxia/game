<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Application\EventListener;

use App\Modules\Demeter\Domain\Event\Government\FiredMinisterEvent;
use App\Modules\Demeter\Domain\Event\Government\NewMinisterEvent;
use App\Modules\Demeter\Domain\Event\NewCandidateEvent;
use App\Modules\Demeter\Domain\Event\NewDemocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewPutschAttemptEvent;
use App\Modules\Demeter\Domain\Event\NewRoyalisticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewTheocraticLeaderEvent;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Galaxy\Domain\Event\SectorOwnerChangeEvent;
use App\Modules\Hermes\Application\Persister\NewsPersister;
use App\Modules\Hermes\Domain\Event\NewsEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(NewDemocraticLeaderEvent::class)]
#[AsEventListener(NewRoyalisticLeaderEvent::class)]
#[AsEventListener(NewTheocraticLeaderEvent::class)]
#[AsEventListener(NewCandidateEvent::class)]
#[AsEventListener(NewPutschAttemptEvent::class)]
#[AsEventListener(SectorOwnerChangeEvent::class)]
#[AsEventListener(NewMinisterEvent::class)]
#[AsEventListener(FiredMinisterEvent::class)]
final readonly class NewsListener
{
	public function __construct(
		private ColorRepositoryInterface $factionRepository,
		private NewsPersister $newsPersister,
	) {
	}

	public function __invoke(NewsEvent $event): void
	{
		$factions = $this->factionRepository->getInGameFactions();

		foreach ($event->getNewsBuilders($factions) as $newsBuilder) {
			$this->newsPersister->saveFromBuilder($newsBuilder);
		}
	}
}
