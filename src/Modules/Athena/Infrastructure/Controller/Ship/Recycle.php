<?php

namespace App\Modules\Athena\Infrastructure\Controller\Ship;

use App\Modules\Ares\Domain\Service\GetShipCategoriesConfiguration;
use App\Modules\Gaia\Domain\Entity\Planet;
use App\Modules\Gaia\Manager\PlanetManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Recycle extends AbstractController
{
	public function __invoke(
		Request                        $request,
		Player                         $currentPlayer,
		Planet                         $currentBase,
		PlanetManager                  $planetManager,
		GetShipCategoriesConfiguration $getShipCategoriesConfiguration,
	): Response {
		$typeOfShip = $request->query->get('ship_identifier');
		$quantity = $request->request->get('quantity');

		if (false !== $typeOfShip and false !== $quantity) {
			if ($quantity > 0 && $quantity <= $currentBase->getShipStorage()[$typeOfShip]) {
				$resources = ($quantity * $getShipCategoriesConfiguration($typeOfShip, 'resourcePrice')) / 2;
				$currentBase->removeShips($typeOfShip, $quantity);
				$planetManager->increaseResources($currentBase, $resources);

				return $this->redirect($request->headers->get('referer'));
			} else {
				throw new ConflictHttpException('cette quantité ne correspond pas à votre stock');
			}
		} else {
			throw new BadRequestHttpException('pas assez d\'informations');
		}
	}
}
