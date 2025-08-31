<?php

namespace App\Modules\Athena\Infrastructure\Controller\Financial;

use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdatePlanetInvestments extends AbstractController
{
	public function __invoke(
		Request                   $request,
		Player                    $currentPlayer,
		PlanetRepositoryInterface $planetRepository,
		Planet                    $currentPlanet,
		string                    $category,
	): Response {
		$credit = $request->request->getInt('credit');

		match ($category) {
			'school' => $this->updateSchoolInvestment($currentPlanet, $credit),
			'antispy' => $this->updateAntiSpyInvestment($currentPlanet, $credit),
			default => throw new BadRequestHttpException('Invalid category'),
		};

		$planetRepository->save($currentPlanet);

		return $this->redirectToRoute('financial_investments');
	}

	protected function updateSchoolInvestment(Planet $planet, int $credit): void
	{
		if (50000 < $credit) {
			throw new BadRequestHttpException('La limite maximale d\'investissement dans l\'école de commandement est de 50\'000 crédits.');
		}
		$planet->iSchool = $credit;
		$this->addFlash('success', 'L\'investissement dans l\'école de commandement de votre base '.$planet->name.' a été modifié');
	}

	protected function updateAntiSpyInvestment(Planet $planet, int $credit): void
	{
		if (100000 < $credit) {
			throw new BadRequestHttpException('La limite maximale d\'investissement dans l\'anti-espionnage est de 100\'000 crédits.');
		}
		$planet->iAntiSpy = $credit;
		$this->addFlash('success', 'L\'investissement dans l\'anti-espionnage sur votre base '.$planet->name.' a été modifié');
	}
}
