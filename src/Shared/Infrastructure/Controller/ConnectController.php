<?php

namespace App\Shared\Infrastructure\Controller;

use App\Classes\Container\ArrayList;
use App\Classes\Container\EventList;
use App\Classes\Library\Utils;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Portal\Domain\Entity\User;
use App\Modules\Zeus\Domain\Event\PlayerConnectionEvent;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class ConnectController extends AbstractController
{
	public function __construct(private readonly PlanetRepositoryInterface $planetRepository)
	{
	}

	public function __invoke(
		Request $request,
		Security $security,
		PlayerRepositoryInterface $playerRepository,
		EntityManagerInterface $entityManager,
		EventDispatcherInterface $eventDispatcher,
		LoggerInterface $logger,
		int $playerId
	): Response {
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

		$session = $request->getSession();

		if (null === ($player = $playerRepository->get($playerId)) || !$player->canAccess()) {
			$logger->debug('Player not found or cannot access game');

			return $this->redirectToRoute('player_choice');
		}

		/** @var User $user */
		$user = $this->getUser();
		if ($player->user->getId() !== $user->getId()) {
			throw $this->createAccessDeniedException('This player account does not belong to you');
		}

		$player->statement = Player::ACTIVE;

		$session->set('token', Utils::generateString(5));

		$this->createSession($session, $player);

		// mise de dLastConnection + dLastActivity
		$player->dLastConnection = new \DateTimeImmutable();
		$player->dLastActivity = new \DateTimeImmutable();

		// confirmation au portail
		// TODO Replace when portal is implemented
		/*if ('enabled' === $this->getParameter('apimode')) {
			$api->confirmConnection($bindKey);
		}*/
		$entityManager->flush($player);

		$eventDispatcher->dispatch(new PlayerConnectionEvent($player));
		// redirection vers page de départ
		return $this->redirectToRoute('profile', [
			'mode' => ('splash' === $request->query->get('mode'))
				? 'profil/mode-splash'
				: 'profil',
		]);
	}

	private function createSession(Session $session, Player $player): void
	{
		// remplissage des données du joueur
		$session->set('playerId', $player->id);

		$playerPlanets = $this->planetRepository->getPlayerPlanets($player);
		// remplissage des bonus

		// création des paramètres utilisateur
		$session->set('playerParams', new ArrayList());

		$session->set('playerInfo', new ArrayList());

		// remplissage des paramètres utilisateur
		$session->get('playerParams')->add('planet', $playerPlanets[0]->id);

		// création des tableaux de données dans le contrôleur

		$session->set('playerEvent', new EventList());
	}
}
