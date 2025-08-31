<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Classes\Container\Params;
use App\Classes\Library\Game;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Gaia\Application\Registry\CurrentPlayerPlanetsRegistry;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class ViewHeadquarters extends AbstractController
{
	public function __construct(
		private readonly CurrentPlayerPlanetsRegistry $currentPlayerBasesRegistry,
		private readonly CommanderRepositoryInterface $commanderRepository,
	) {

	}

	public function __invoke(
		Request $request,
		Player $currentPlayer,
	): Response {
		$commander = $commanderBase = null;
		if (null !== ($commanderId = $request->query->get('commander'))) {
			if (!Uuid::isValid($commanderId)) {
				throw new BadRequestHttpException('Invalid Commander ID');
			}
			$commander = $this->commanderRepository->get(Uuid::fromString($commanderId));
		}

		if (null !== $commander && $commander->player->id === $currentPlayer->id && in_array($commander->statement, [Commander::AFFECTED, Commander::MOVING])) {
			$commanderBase = $commander->base;
		}

		[$obsets, $commandersIds] = $this->getObsetsAndCommandersIds($request, $currentPlayer);

		return $this->render('pages/ares/fleet/headquarters.html.twig', [
			'commander' => $commander ?? null,
			'commander_base' => $commanderBase ?? null,
			'default_parameters' => Params::$params,
			'obsets' => $obsets,
			'commandersIds' => $commandersIds,
		]);
	}

	private function getObsetsAndCommandersIds(
		Request $request,
		Player $currentPlayer,
	): array {
		$session = $request->getSession();
		$obsets = [];
		foreach ($this->currentPlayerBasesRegistry->all() as $planet) {
			if ($request->cookies->get('p'.Params::LIST_ALL_FLEET, Params::$params[Params::LIST_ALL_FLEET]) || $planet->id->equals($this->currentPlayerBasesRegistry->current()->id)) {
				$obsets[] = [
					'info' => [
						'id' => $planet->id,
						'name' => $planet->name,
						'type' => $planet->typeOfBase,
						'img' => '1-'.Game::getSizeOfPlanet($planet->place->population),
					],
					'fleets' => [],
				];
			}
		}

		// commander manager : incoming attack
		$commandersId = [0];
		for ($i = 0; $i < $session->get('playerEvent')->size(); ++$i) {
			if ($session->get('playerEvent')->get($i)->get('eventType') == $this->getParameter('event_incoming_attack')) {
				if ($session->get('playerEvent')->get($i)->get('eventInfo')->size() > 0) {
					$commandersId[] = $session->get('playerEvent')->get($i)->get('eventId');
				}
			}
		}

		$attackingCommanders = $this->commanderRepository->getIncomingAttacks($currentPlayer);
		for ($i = 0; $i < count($obsets); ++$i) {
			foreach ($attackingCommanders as $commander) {
				if ($commander->destinationPlace->id->equals($obsets[$i]['info']['id'])) {
					$obsets[$i]['fleets'][] = $commander;
				}
			}
		}
		$commanders = $this->commanderRepository->getPlayerCommanders(
			$currentPlayer,
			[Commander::AFFECTED, Commander::MOVING],
			['c.base' => 'DESC'],
		);

		for ($i = 0; $i < count($obsets); ++$i) {
			foreach ($commanders as $commander) {
				if ($commander->base->id->equals($obsets[$i]['info']['id'])) {
					$obsets[$i]['fleets'][] = $commander;
				}
			}
		}

		return [$obsets, $commandersId];
	}
}
