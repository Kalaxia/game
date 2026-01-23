<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Workflow\FactionMandate;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Service\Configuration\GetFactionsConfiguration;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\MandateState;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\Attribute\AsEnterListener;
use Symfony\Component\Workflow\Event\EnterEvent;

readonly class MissingCandidatesWorkflowEventListener
{
	public function __construct(
		private EventDispatcherInterface $eventDispatcher,
		private MessageBusInterface $messageBus,
		private NotificationRepositoryInterface $notificationRepository,
		private PlayerRepositoryInterface $playerRepository,
		private GetFactionsConfiguration $getFactionsConfiguration,
		private NextElectionDateCalculator $nextElectionDateCalculator,
	) {
	}


	#[AsEnterListener(workflow: 'faction_mandate', place: MandateState::Active->value)]
	public function onMissingCandidates(EnterEvent $event): void
	{
		if ($event->getTransition()->getName() !== 'missing_candidates') {
			return;
		}

		/** @var Color $faction */
		$faction = $event->getSubject();

		$this->messageBus->dispatch(
			new CampaignMessage($faction->id),
			[DateTimeConverter::to_delay_stamp(
				$this->nextElectionDateCalculator->getCampaignStartDate($faction),
			)],
		);

		$previousLeader = $this->playerRepository->getFactionLeader($faction);

		if (null !== $previousLeader && Color::REGIME_DEMOCRATIC === $faction->regime) {
			$this->notificationRepository->save(NotificationBuilder::new()
				->setTitle('Vous demeurez '.($this->getFactionsConfiguration)($faction, 'status')[Player::CHIEF - 1])
				->setContent(NotificationBuilder::paragraph(
					'Aucun candidat ne s\'est présenté oour vous remplacer lors des dernières élections.',
					'Par conséquent, vous êtes toujours à la tête de ',
					($this->getFactionsConfiguration)($faction, 'popularName'),
				))
				->for($previousLeader));
		} elseif (Color::REGIME_THEOCRATIC === $faction->regime) {
			$this->notificationRepository->save(NotificationBuilder::new()
				->setTitle('Vous avez été nommé Guide')
				->setContent(NotificationBuilder::paragraph(
					'Les Oracles ont parlé,',
					' vous êtes toujours désigné par la Grande Lumière pour guider Cardan vers la Gloire.',
				))
				->for($previousLeader));
		}
	}
}
