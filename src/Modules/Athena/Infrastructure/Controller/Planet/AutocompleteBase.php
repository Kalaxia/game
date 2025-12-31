<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Planet;

use App\Modules\Galaxy\Domain\Repository\PlaceRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

class AutocompleteBase extends AbstractController
{
	#[Route(
		path: '/api/planets/{search}',
		name: 'autocomplete_planet',
		methods: [Request::METHOD_GET],
	)]
	public function __invoke(
		Request $request,
		PlaceRepositoryInterface $placeRepository,
	): Response {
		if (null === ($search = $request->query->get('q'))) {
			throw new BadRequestHttpException('Missing search parameter');
		}

		return $this->render('blocks/athena/autocomplete_planet.html.twig', [
			'places' => $placeRepository->search($search),
		]);
	}
}
