<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Modules\Ares\Application\Handler\CommanderExperienceHandler;
use App\Modules\Ares\Domain\Event\Commander\NewCommanderEvent;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Resource\PlaceResource;
use App\Modules\Zeus\Helper\CheckName;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class CreateCommander extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		CommanderExperienceHandler $commanderExperienceHandler,
		CommanderRepositoryInterface $commanderRepository,
		PlayerManager $playerManager,
		EventDispatcherInterface $eventDispatcher,
		#[Autowire('%app.commander_school.credits_cost%')]
		int $creditsCost,
		#[Autowire('%app.commander_school.minimum_experience%')]
		int $minimumExperience,
		#[Autowire('%app.commander_school.maximum_experience%')]
		int $maximumExperience,
	): Response {
		$name = $request->request->get('name') ?? throw new BadRequestHttpException('Missing name');

		$cn = new CheckName();
		$cn->maxLength = 20;

		$schoolCommanders = $commanderRepository->getBaseCommanders($currentBase, [Commander::INSCHOOL]);

		if (count($schoolCommanders) >= PlaceResource::get($currentBase->typeOfBase, 'school-size')) {
			throw new ConflictHttpException('Trop d\'officiers en formation. Déplacez des officiers dans le mess pour libérer de la place.');
		}
		$reserveCommanders = $commanderRepository->getBaseCommanders($currentBase, [Commander::RESERVE]);

		if (count($reserveCommanders) >= OrbitalBase::MAXCOMMANDERINMESS) {
			throw new ConflictHttpException('Vous ne pouvez pas créer de nouveaux officiers si vous en avez déjà '.Orbitalbase::MAXCOMMANDERINMESS.' ou plus.');
		}
		// TODO Replace with validator component
		if (!$cn->checkLength($name) || !$cn->checkChar($name)) {
			throw new BadRequestHttpException('le nom contient des caractères non autorisé ou trop de caractères.');
		}
		// TODO Replace with specification
		if ($creditsCost > $currentPlayer->getCredits()) {
			throw new AccessDeniedHttpException('vous n\'avez pas assez de crédit.');
		}

		$playerManager->decreaseCredit($currentPlayer, $creditsCost);

		$newCommander = new Commander(
			id: Uuid::v4(),
			name: $name,
			avatar: 't'.random_int(1, 21).'-c'.$currentPlayer->faction->identifier,
			player: $currentPlayer,
			base: $currentBase,
			enlistedAt: new \DateTimeImmutable(),
			sexe: 1,
			age: random_int(40, 70),
			updatedAt: new \DateTimeImmutable(),
		);
		$commanderExperienceHandler->upExperience(
			$newCommander,
			random_int(
				$minimumExperience,
				$maximumExperience,
			),
		);
		$commanderRepository->save($newCommander);

		$eventDispatcher->dispatch(new NewCommanderEvent($newCommander, $currentPlayer));

		$this->addFlash('success', 'Commandant inscrit au programme d\'entraînement.');

		return $this->redirectToRoute('school');
	}
}
