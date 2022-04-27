<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base;

use App\Modules\Gaia\Manager\PlaceManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AutocompleteBase extends AbstractController
{
    public function __invoke(
        Request $request,
        PlaceManager $placeManager,
    ): Response {
        if (null === ($search = $request->query->get('q'))) {
            throw new BadRequestHttpException('Missing search parameter');
        }

        return $this->render('blocks/athena/autocomplete_base.html.twig', [
            'places' => $placeManager->search($search),
        ]);
    }
}
