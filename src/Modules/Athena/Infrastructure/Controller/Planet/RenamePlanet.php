<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Planet;

use App\Classes\Library\Parser;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Manager\PlanetManager;
use App\Modules\Zeus\Helper\CheckName;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

class RenamePlanet extends AbstractController
{
	#[Route(
		path: '/rename-planet',
		name: 'rename_planet',
		methods: [Request::METHOD_POST],
	)]
	public function __invoke(
		Request                $request,
		Player                 $currentPlayer,
		Planet                 $currentPlanet,
		Parser                 $parser,
		PlanetManager          $planetManager,
		EntityManagerInterface $entityManager,
	): Response {
		// protection du nouveau nom de la base
		$name = $parser->protect($request->request->get('name'));

		if (empty($name)) {
			throw new BadRequestHttpException('Nom invalide');
		}
		$check = new CheckName();
		$check->setMaxLength(20);

		if (!$check->checkLength($name)) {
			throw new BadRequestHttpException('modification du nom de la base orbitale impossible - nom trop long ou trop court');
		}
		if (!$check->checkChar($name)) {
			throw new BadRequestHttpException('modification du nom de la base orbitale impossible - le nom contient des caractères non-autorisés');
		}
		$currentPlanet->name = $name;

		$entityManager->flush();

		$this->addFlash('success', 'Le nom a été changé en ' . $name . ' avec succès');

		return $this->redirectToRoute('base_overview');
	}
}
