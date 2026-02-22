<?php

namespace App\Modules\Promethee\Infrastructure\Controller;

use App\Modules\Promethee\Domain\Repository\ResearchRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Helper\ResearchHelper;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Manager\ResearchManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewTechnologyPanel extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		TechnologyRepositoryInterface $technologyRepository,
		TechnologyHelper $technologyHelper,
		ResearchRepositoryInterface $researchRepository,
		ResearchManager $researchManager,
		ResearchHelper $researchHelper,
		int $identifier,
	): Response {
		if (!$technologyHelper->isATechnology($identifier)) {
			throw $this->createNotFoundException('This technology does not exist');
		}

		if ($technologyHelper->isATechnologyNotDisplayed($identifier)) {
			throw $this->createAccessDeniedException('You do not have access to this technology');
		}

		$technos = $technologyRepository->getPlayerTechnology($currentPlayer);
		$research = $researchRepository->getPlayerResearch($currentPlayer);

		$level = $technos->getTechnology($identifier);

		$shortDescription = $technologyHelper->getInfo($identifier, 'shortDescription');
		$improvementPercentage = $technologyHelper->getImprovementPercentage($identifier, $level + 1);
		$shortDescription = str_replace('{x}', $improvementPercentage, $shortDescription);

		$requiredResearch = $technologyHelper->getInfo($identifier, 'requiredResearch');

		$researchList = [];
		$researchQuantity = intval($this->getParameter('promethee.research.quantity'));
		for ($i = 0; $i < $researchQuantity; ++$i) {
			if ($requiredResearch[$i] > 0) {
				$check = true;
				if ($researchManager->getResearchList($research)->get($i) < ($requiredResearch[$i] + $level)) {
					$check = false;
				}
				$researchList[] = [$researchHelper->getInfo($i, 'name'), $requiredResearch[$i] + $level, $check];
			}
		}

		return $this->render('blocks/promethee/technology_panel.html.twig', [
			'research_list' => $researchList,
			'technosphere' => $technologyHelper->getInfo($identifier, 'requiredTechnosphere'),
			'description' => $technologyHelper->getInfo($identifier, 'description'),
			'short_description' => $shortDescription,
			'name' => $technologyHelper->getInfo($identifier, 'name'),
			'image' => $technologyHelper->getInfo($identifier, 'imageLink'),
			'time' => $technologyHelper->getInfo($identifier, 'time', $level + 1),
			'resource' => $technologyHelper->getInfo($identifier, 'resource', $level + 1),
			'credit' => $technologyHelper->getInfo($identifier, 'credit', $level + 1),
			'points' => $technologyHelper->getInfo($identifier, 'points', $level + 1),
		]);
	}
}
