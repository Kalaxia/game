<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Classes\Library\Format;
use App\Modules\Ares\Application\Handler\CommanderExperienceHandler;
use App\Modules\Ares\Domain\Event\Commander\NewCommanderEvent;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Resource\SchoolClassResource;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Resource\PlaceResource;
use App\Modules\Zeus\Helper\CheckName;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class CreateCommander extends AbstractController
{
	public function __invoke(
        Request                      $request,
        Player                       $currentPlayer,
        Planet                       $currentBase,
        CommanderExperienceHandler   $commanderExperienceHandler,
        CommanderRepositoryInterface $commanderRepository,
        PlayerManager                $playerManager,
        EventDispatcherInterface     $eventDispatcher,
	): Response {
		$school = 0;
		$name = $request->request->get('name') ?? throw new BadRequestHttpException('Missing name');

		$cn = new CheckName();
		$cn->maxLength = 20;

		$schoolCommanders = $commanderRepository->getPlanetCommanders($currentBase, [Commander::INSCHOOL]);

		if (count($schoolCommanders) >= PlaceResource::get($currentBase->typeOfBase, 'school-size')) {
			throw new ConflictHttpException('Trop d\'officiers en formation. Déplacez des officiers dans le mess pour libérer de la place.');
		}
		$reserveCommanders = $commanderRepository->getPlanetCommanders($currentBase, [Commander::RESERVE]);

		if (count($reserveCommanders) >= Planet::MAXCOMMANDERINMESS) {
			throw new ConflictHttpException('Vous ne pouvez pas créer de nouveaux officiers si vous en avez déjà '.Planet::MAXCOMMANDERINMESS.' ou plus.');
		}
		$nbrCommandersToCreate = random_int(SchoolClassResource::getInfo($school, 'minSize'), SchoolClassResource::getInfo($school, 'maxSize'));

		// TODO Replace with validator component
		if (!$cn->checkLength($name) || !$cn->checkChar($name)) {
			throw new BadRequestHttpException('le nom contient des caractères non autorisé ou trop de caractères.');
		}
		// TODO Replace with specification
		if (SchoolClassResource::getInfo($school, 'credit') > $currentPlayer->getCredits()) {
			throw new AccessDeniedHttpException('vous n\'avez pas assez de crédit.');
		}
		// débit des crédits au joueur
		$playerManager->decreaseCredit($currentPlayer, SchoolClassResource::getInfo($school, 'credit'));

		for ($i = 0; $i < $nbrCommandersToCreate; ++$i) {
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
					SchoolClassResource::getInfo($school, 'minExp'),
					SchoolClassResource::getInfo($school, 'maxExp'),
				),
			);
			$commanderRepository->save($newCommander);

			$eventDispatcher->dispatch(new NewCommanderEvent($newCommander, $currentPlayer));
		}
		$this->addFlash('success', sprintf(
			'%s commandant%s inscrit%s au programme d\'entraînement.',
			$nbrCommandersToCreate,
			Format::addPlural($nbrCommandersToCreate),
			Format::addPlural($nbrCommandersToCreate),
		));

		return $this->redirectToRoute('school');
	}
}
