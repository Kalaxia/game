<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Controller\Registration;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Domain\Service\GetAvailableAvatars;
use App\Modules\Zeus\Helper\CheckName;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CreateCharacter extends AbstractController
{
	#[Route(
		path: '/registration/create-character',
		name: 'registration_create_character',
		methods: ['GET', 'POST'],
	)]
	public function __invoke(
		Request $request,
		ColorRepositoryInterface $colorRepository,
		GetAvailableAvatars $getAvailableAvatars,
		PlayerRepositoryInterface $playerRepository,
	): Response {
		$session = $request->getSession();
		if (!$session->has('inscription')) {
			// TODO Replace when portal is implemented
			return $this->redirect($this->getParameter('getout_root') . 'serveurs/message-forbiddenaccess');
		}

		if (Request::METHOD_POST === $request->getMethod()) {
			if (null === $playerRepository->getByName($request->request->get('pseudo'))) {
				$check = new CheckName();

				if ($request->request->has('pseudo') && $check->checkLength($request->request->get('pseudo')) && $check->checkChar($request->request->get('pseudo'))) {
					$session->get('inscription')->add('pseudo', $request->request->get('pseudo'));

					$faction = $colorRepository->getOneByIdentifier((int) $session->get('inscription')->get('ally'))
						?? throw $this->createNotFoundException('Faction inconnue');

					// check avatar
					if (in_array($request->request->get('avatar'), $getAvailableAvatars($faction))) {
						$session->get('inscription')->add('avatar', $request->request->get('avatar'));

						return $this->redirectToRoute('registration_choose_place');
					} elseif (!$session->get('inscription')->exist('avatar')) {
						throw new BadRequestHttpException('Cet avatar n\'existe pas ou est invalide');
					}
				} elseif (!$session->get('inscription')->exist('pseudo')) {
					$this->addFlash('error', 'Votre pseudo est trop long, trop court ou contient des caractères non-autorisés');

					return $this->redirectToRoute('registration_create_character');
				}
			} elseif (!$session->get('inscription')->exist('pseudo')) {
				$this->addFlash('error', 'Ce pseudo est déjà utilisé par un autre joueur');

				return $this->redirectToRoute('registration_create_character');
			}
		}

		// création du tableau des alliances actives
		// entre 1 et 7
		// alliance pas défaites
		// algorythme de fermeture automatique des alliances (auto-balancing)
		$openFactions = $colorRepository->getOpenFactions();

		$ally = array_map(fn (Color $faction) => $faction->identifier, $openFactions);

		if ($request->query->has('factionIdentifier') && in_array($request->query->get('factionIdentifier'), $ally)) {
			$session->get('inscription')->add('ally', $request->query->get('factionIdentifier'));
		} elseif (!$session->get('inscription')->exist('ally')) {
			throw new BadRequestHttpException('faction inconnues ou non-sélectionnable');
		}

		$faction = $colorRepository->getOneByIdentifier(intval($session->get('inscription')->get('ally')))
			?? throw new BadRequestHttpException('faction inconnu');

		return $this->render('pages/zeus/registration/profile.html.twig', [
			'chosenFaction' => $faction,
			'avatars' => $getAvailableAvatars($faction),
		]);
	}
}
