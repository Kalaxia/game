<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Controller\CombatSimulator;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
	path: '/combat-simulator',
	name: 'combat_simulator',
	methods: [Request::METHOD_GET],
)]
class ViewSimulator extends AbstractController
{
	public function __invoke(
		#[Autowire('%ares.battle.turn_duration%')]
		int $turnDuration,
	): Response {
		return $this->render('pages/portal/combat-simulator.html.twig', [
			'battle_turn_duration' => $turnDuration,
		]);
	}
}
