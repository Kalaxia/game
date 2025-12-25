<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Controller;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Galaxy\Domain\Entity\Planet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewPlanetActivities extends AbstractController
{
	#[Route(
		path: '/economy/view-planet-activities',
		name: 'economy_view_planet_activities',
		methods: [Request::METHOD_GET],
	)]
	public function __invoke(
		Planet $currentPlanet,
	): Response {
		return $this->render('pages/economy/view_planet_activities.html.twig', [

		]);
	}
}
