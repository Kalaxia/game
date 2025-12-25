<?php

namespace App\Shared\Infrastructure\EventSubscriber;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Galaxy\Application\Registry\CurrentPlayerPlanetsRegistry;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{
	protected SessionInterface|null $session = null;

	public function __construct(
		private readonly Environment                        $twig,
		private readonly CommanderRepositoryInterface       $commanderRepository,
		private readonly ConversationRepositoryInterface    $conversationRepository,
		private readonly NotificationRepositoryInterface    $notificationRepository,
		private readonly CurrentPlayerRegistry              $currentPlayerRegistry,
		private readonly CurrentPlayerPlanetsRegistry       $currentPlayerPlanetsRegistry,
	) {
	}

	public static function getSubscribedEvents(): array
	{
		return [
			ControllerEvent::class => [
				['setCurrentPlayer'],
				['setCurrentBase'],
			],
		];
	}

	public function setCurrentBase(): void
	{
		if (!$this->currentPlayerRegistry->has()) {
			return;
		}

		$player = $this->currentPlayerRegistry->get();
		$currentPlanet = $this->currentPlayerPlanetsRegistry->current();

		$this->twig->addGlobal('current_planet', $currentPlanet);
		$this->twig->addGlobal('current_player_planets', $this->currentPlayerPlanetsRegistry->all());
		$this->twig->addGlobal('first_planet', $this->currentPlayerPlanetsRegistry->first());
		$this->twig->addGlobal('next_planet', $this->currentPlayerPlanetsRegistry->next());
		$this->twig->addGlobal('incoming_commanders', $this->commanderRepository->getIncomingAttacks($player));
		$this->twig->addGlobal('outgoing_commanders', $this->commanderRepository->getOutcomingAttacks($player));
	}

	public function setCurrentPlayer(): void
	{
		if (!$this->currentPlayerRegistry->has()) {
			return;
		}
		$currentPlayer = $this->currentPlayerRegistry->get();

		$this->twig->addGlobal('current_player', $currentPlayer);
		$this->twig->addGlobal('current_player_faction_id', $currentPlayer->faction->identifier);
		$this->twig->addGlobal('conversations_count', $this->conversationRepository->countPlayerUnreadConversations($currentPlayer));
		$this->twig->addGlobal('current_player_notifications', $this->notificationRepository->getUnreadNotifications($currentPlayer, 15));
		$this->twig->addGlobal('current_player_notifications_count', $this->notificationRepository->countUnreadNotifications($currentPlayer));
	}
}
