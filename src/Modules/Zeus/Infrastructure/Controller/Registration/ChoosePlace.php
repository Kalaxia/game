<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Controller\Registration;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Galaxy\Galaxy\GalaxyConfiguration;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Domain\Service\GetAvailableAvatars;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

class ChoosePlace extends AbstractController
{
	#[Route(
		path: '/registration/place-choice',
		name: 'registration_choose_place',
		defaults: [
			'highMode' => false,
		],
		methods: ['GET', 'POST'],
	)]
	public function __invoke(
		Request $request,
		ColorRepositoryInterface $colorRepository,
		GetAvailableAvatars $getAvailableAvatars,
		PlayerRepositoryInterface $playerRepository,
		GalaxyConfiguration $galaxyConfiguration,
		SectorRepositoryInterface $sectorRepository,
	): Response {
		$session = $request->getSession();
		$faction = $colorRepository->getOneByIdentifier(intval($session->get('inscription')->get('ally')))
			?? throw new BadRequestHttpException('faction inconnu');

		return $this->render('pages/zeus/registration/place_choice.html.twig', [
			'chosenFaction' => $faction,
			'galaxy_configuration' => $galaxyConfiguration,
			'sectors' => $sectorRepository->getAll(),
		]);
	}
}
