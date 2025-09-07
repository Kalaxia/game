<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Ui\Http;

use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\SystemRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewMapSandbox extends AbstractController
{
    public function __invoke(SectorRepositoryInterface $sectorRepository, SystemRepositoryInterface $systemRepository): Response
	{
		return $this->render('pages/galaxy/map_sandbox.html.twig', [
			'sectors' => $sectorRepository->getAll(),
			'systems' => $systemRepository->getAll(),
		]);
	}
}
