<?php

namespace App\Modules\Artemis\Application\EventListener;

use App\Modules\Artemis\Domain\Event\SpyEvent;
use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener]
readonly class SpyingNotificationEventListener
{
	public function __construct(
		private NotificationRepositoryInterface $notificationRepository,
		private UrlGeneratorInterface $urlGenerator,
	) {
	}

	public function __invoke(SpyEvent $event): void
	{
		$notificationBuilder = match ($event->spyReport->type) {
			SpyReport::TYP_ANONYMOUSLY_CAUGHT => NotificationBuilder::new()
				->setTitle('Espionnage détecté')
				->setContent(NotificationBuilder::paragraph(
					'Un joueur a espionné votre base ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('map', ['place' => $event->spyReport->place->id]),
						$event->spyReport->placeName,
					),
					'.',
					NotificationBuilder::divider(),
					'Malheureusement, nous n\'avons pas pu connaître l\'identité de l\'espion.'
				)),
			SpyReport::TYP_CAUGHT => NotificationBuilder::new()
				->setTitle('Espionnage intercepté')
				->setContent(NotificationBuilder::paragraph(
					NotificationBuilder::link(
						$this->urlGenerator->generate('embassy', ['player' => $event->player]),
						$event->player->name,
					),
					' a espionné votre base ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('map', ['place' => $event->spyReport->place->id]),
						$event->spyReport->placeName,
					),
					'.',
					NotificationBuilder::divider(),
					'L\'espion s\'est fait attrapé en train de fouiller dans vos affaires.',
				)),
			default => null,
		};

		if (null === $notificationBuilder) {
			return;
		}

		$this->notificationRepository->save($notificationBuilder->for($event->spyReport->targetPlayer));
	}
}
