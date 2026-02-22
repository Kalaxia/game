<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Controller\Registration;

use App\Classes\Container\ArrayList;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChooseFaction extends AbstractController
{
	#[Route(
		path: '/registration/faction-choice/{highMode}',
		name: 'registration_choose_faction',
		defaults: [
			'highMode' => false,
		],
		methods: ['GET', 'POST'],
	)]
	public function __invoke(
		Request $request,
		ColorRepositoryInterface $colorRepository,
		bool $highMode,
	): Response {
		$session = $request->getSession();
		// mode de création de joueur
		$session->set('high-mode', $this->getParameter('highmode') && $highMode);
		$session->set('inscription', new ArrayList());

		return $this->render('pages/zeus/registration/faction_choice.html.twig', [
			'sorted_factions' => $colorRepository->getAllByActivePlayersNumber(),
		]);
	}
}
