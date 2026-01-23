<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsFromFaction;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Event\EnterEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

readonly class RoyalisticPutschWorkflowEventListener
{
	public function __construct(
		private NotificationRepositoryInterface $notificationRepository,
		private PlayerRepositoryInterface $playerRepository,
		private UrlGeneratorInterface $urlGenerator,
		private NextElectionDateCalculator $nextElectionDateCalculator,
		private MessageBusInterface $messageBus,
	) {
	}

	#[AsGuardListener(workflow: 'faction_mandate', transition: 'royalistic_putsch')]
	public function guard(GuardEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		if (Color::REGIME_ROYALISTIC !== $faction->regime) {
			$event->setBlocked(true, sprintf('Faction %s is not royalistic', $faction->identifier));
		}

		if (MandateState::Active !== $faction->mandateState) {
			$event->setBlocked(true, sprintf('There already is an ongoing coup in faction %s', $faction->identifier));
		}
	}

	#[AsEnterListener(workflow: 'faction_mandate', place: MandateState::Putsch->value)]
	public function onPutschStart(EnterEvent $event): void
	{
		/** @var Color $faction */
		$faction = $event->getSubject();

		$factionPlayers = $this->playerRepository->getBySpecification(new IsFromFaction($faction));

		$notificationBuilder = NotificationBuilder::new()
			->setTitle('Coup d\'Etat.')
			->setContent(NotificationBuilder::paragraph(
				'Un membre de votre Faction soulève une partie du peuple et tente un coup d\'état contre le gouvernement.',
				NotificationBuilder::divider(),
				NotificationBuilder::link(
					$this->urlGenerator->generate('view_faction_election'),
					'prendre parti sur le coup d\'état.',
				),
			));

		foreach ($factionPlayers as $factionPlayer) {
			if (Player::ACTIVE !== $factionPlayer->statement) {
				continue;
			}
			$this->notificationRepository->save($notificationBuilder->for($factionPlayer));
		}

		$this->messageBus->dispatch(
			new BallotMessage($faction->id),
			[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getPutschEndDate($faction))],
		);
	}
}
