<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Controller\Registration;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Portal\Domain\Entity\User;
use App\Modules\Zeus\Application\Factory\PlayerFactory;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Helper\CheckName;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class Save extends AbstractController
{
	#[Route(
		path: '/registration/save',
		name: 'registration_save',
		methods: ['POST'],
	)]
	public function __invoke(
		Request $request,
		ColorRepositoryInterface $colorRepository,
		PlayerRepositoryInterface $playerRepository,
		PlayerFactory $playerFactory,
		SectorRepositoryInterface $sectorRepository,
	): Response {
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

		$session = $request->getSession();
		if ($session->has('inscription')) {
			$check = new CheckName();

			if ($request->request->has('base') && $check->checkLength($request->request->get('base'))) {
				if ($check->checkChar($request->request->get('base'))) {
					$session->get('inscription')->add('base', $request->request->get('base'));

					$sectors = $sectorRepository->getAll();

					$factionSectors = [];
					foreach ($sectors as $sector) {
						if ($sector->faction?->identifier == $session->get('inscription')->get('ally')) {
							$factionSectors[] = $sector->id;
						}
					}
					if (in_array($request->request->get('sector'), $factionSectors)) {
						$session->get('inscription')->add('sector', $request->request->get('sector'));
					} else {
						throw new BadRequestHttpException('il faut sélectionner un des secteurs de la couleur de votre faction');
					}
				} else {
					throw new BadRequestHttpException('le nom de votre base ne doit pas contenir de caractères spéciaux');
				}
			} else {
				throw new BadRequestHttpException('le nom de votre base doit contenir entre '.$check->getMinLength().' et '.$check->getMaxLength().' caractères');
			}
		} else {
			// TODO Replace when portal is implemented
			return $this->redirect($this->getParameter('getout_root').'serveurs/message-forbiddenaccess');
		}

		try {
			$session = $request->getSession();

			$faction = $colorRepository->getOneByIdentifier(intval($session->get('inscription')->get('ally')))
				?? throw new BadRequestHttpException('Invalid faction identifier');

			$godFather = null;
			if ($session->has('rgodfather')) {
				$godFather = $playerRepository->get($session->get('rgodfather'))
					?? throw new BadRequestHttpException('Godfather not found');
			}
			$sector = $sectorRepository->get(Uuid::fromString($session->get('inscription')->get('sector')))
				?? throw new BadRequestHttpException('Sector not found');

			// remove godFather from session
			$session->remove('rgodfather');

			/** @var User $user */
			$user = $this->getUser();

			$player = $playerFactory->create(
				faction: $faction,
				user: $user,
				name: trim((string) $session->get('inscription')->get('pseudo')),
				avatar: $session->get('inscription')->get('avatar'),
				sector: $sector,
				baseName: $session->get('inscription')->get('base'),
				godFather: $godFather,
				highMode: $session->get('high-mode'),
			);

			// redirection vers connection
			return $this->redirectToRoute('connect', [
				'playerId' => $player->id,
			]);
		} catch (\Throwable $t) {
			// @TODO handle this
			throw $t;
			dd($t);
			// tentative de réparation de l'erreur
			return $this->redirectToRoute('registration_choose_place');
		}
	}
}
