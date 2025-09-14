<?php

namespace App\Modules\Promethee\Infrastructure\Controller;

use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Manager\PlanetManager;
use App\Modules\Promethee\Application\Factory\TechnologyQueueFactory;
use App\Modules\Promethee\Domain\Repository\ResearchRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Manager\ResearchManager;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SearchTechnology extends AbstractController
{
	public function __construct(
		private readonly TechnologyHelper $technologyHelper,
		private readonly ResearchManager $researchManager,
		private readonly ResearchRepositoryInterface $researchRepository,
	) {

	}

	public function __invoke(
		Request                            $request,
		Player                             $currentPlayer,
		Planet                             $currentPlanet,
		TechnologyQueueRepositoryInterface $technologyQueueRepository,
		TechnologyRepositoryInterface      $technologyRepository,
		PlanetManager                      $planetManager,
		PlayerManager                      $playerManager,
		TechnologyQueueFactory             $technologyQueueFactory,
		string                             $identifier,
	): Response {
		if (!$this->technologyHelper->isATechnology($identifier) || $this->technologyHelper->isATechnologyNotDisplayed($identifier)) {
			throw new BadRequestHttpException('la technologie indiquée n\'est pas valide');
		}
		if ($technologyQueueRepository->getPlayerTechnologyQueue($currentPlayer, $identifier) !== null) {
			throw new ConflictHttpException('Cette technologie est déjà en construction');
		}
		$technos = $technologyRepository->getPlayerTechnology($currentPlayer);
		$targetLevel = $technos->getTechnology($identifier) + 1;
		// @TODO I think this piece of code is dead
		$technologyQueues = $technologyQueueRepository->getPlanetQueues($currentPlanet);
		$nbTechnologyQueues = count($technologyQueues);
		foreach ($technologyQueues as $technologyQueue) {
			if ($technologyQueue->technology == $identifier) {
				++$targetLevel;
			}
		}


		if (!$this->haveRights($currentPlanet, $currentPlayer, $nbTechnologyQueues, $identifier, $targetLevel)) {
			throw $this->createAccessDeniedException(
				'les conditions ne sont pas remplies pour développer une technologie',
			);
		}
		$createdAt =
			(0 === $nbTechnologyQueues)
				? new \DateTimeImmutable()
				: $technologyQueues[$nbTechnologyQueues - 1]->getEndDate();

		$technologyQueueFactory->create(
			planet: $currentPlanet,
			identifier: $identifier,
			targetLevel: $targetLevel,
			createdAt: $createdAt,
		);

		$playerManager->decreaseCredit($currentPlayer, $this->technologyHelper->getInfo($identifier, 'credit', $targetLevel));

		// alerte
		$this->addFlash('success', 'Développement de la technologie programmée');

		return $this->redirect($request->headers->get('referer'));
	}

	private function haveRights(
        Planet $currentPlanet,
        Player $currentPlayer,
        int    $technologyQueuesCount,
        int    $identifier,
        int    $targetLevel,
	): bool {
		$research = $this->researchRepository->getPlayerResearch($currentPlayer);

		return $this->technologyHelper->haveRights($identifier, 'resource', $targetLevel, $currentPlanet->resourcesStorage)
			&& $this->technologyHelper->haveRights($identifier, 'credit', $targetLevel, $currentPlayer->getCredits())
			&& $this->technologyHelper->haveRights($identifier, 'queue', $currentPlanet, $technologyQueuesCount)
			&& $this->technologyHelper->haveRights($identifier, 'levelPermit', $targetLevel)
			&& $this->technologyHelper->haveRights($identifier, 'technosphereLevel', $currentPlanet->levelTechnosphere)
			&& $this->technologyHelper->haveRights($identifier, 'research', $targetLevel, $this->researchManager->getResearchList($research))
			&& $this->technologyHelper->haveRights($identifier, 'maxLevel', $targetLevel)
			&& $this->technologyHelper->haveRights($identifier, 'baseType', $currentPlanet->typeOfBase);
	}
}
