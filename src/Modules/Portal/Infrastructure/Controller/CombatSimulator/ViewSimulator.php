<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Controller\CombatSimulator;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
		Request $request,
		ColorRepositoryInterface $colorRepository,
	): Response {
		$session = $request->getSession();

		$commanders = $session->get('combat_simulator.commanders', []);

		return $this->render('pages/portal/combat-simulator.html.twig', [
			'commanders' => $commanders,
		]);
	}
}
