<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewPlanetActivities extends AbstractController
{
	#[Route('/economy/view-planet-activities', name: 'economy_view_planet_activities')]
	public function __invoke(): Response
	{
		return $this->render('pages/economy/view_planet_activities.html.twig', [

		]);
	}
}
