<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Modules\Demeter\Domain\Event\MissingCandidatesEvent;
use App\Modules\Demeter\Domain\Event\NewDemocraticCampaignEvent;
use App\Modules\Demeter\Domain\Event\NewDemocraticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewPutschAttemptEvent;
use App\Modules\Demeter\Domain\Event\NewRoyalisticLeaderEvent;
use App\Modules\Demeter\Domain\Event\NewTheocraticCampaignEvent;
use App\Modules\Demeter\Domain\Event\PutschFailedEvent;
use App\Modules\Demeter\Domain\Event\UniqueCandidateEvent;
use App\Shared\Domain\Event\LoggerEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(MissingCandidatesEvent::class)]
#[AsEventListener(NewDemocraticCampaignEvent::class)]
#[AsEventListener(NewDemocraticLeaderEvent::class)]
#[AsEventListener(NewPutschAttemptEvent::class)]
#[AsEventListener(NewRoyalisticLeaderEvent::class)]
#[AsEventListener(NewTheocraticCampaignEvent::class)]
#[AsEventListener(PutschFailedEvent::class)]
#[AsEventListener(UniqueCandidateEvent::class)]
readonly class LoggerListener
{
	public function __construct(private LoggerInterface $logger)
	{
	}

	public function __invoke(LoggerEvent $event): void
	{
		$event->log($this->logger);
	}
}
