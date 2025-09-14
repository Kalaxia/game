<?php

namespace App\Modules\Ares\Infrastructure\Controller\Commander;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Resource\SchoolClassResource;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Resource\PlaceResource;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Helper\CheckName;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewSchool extends AbstractController
{
	public function __invoke(
        Request                      $request,
        CurrentPlayerBonusRegistry   $currentPlayerBonusRegistry,
        Planet                       $currentBase,
        CommanderRepositoryInterface $commanderRepository,
	): Response {
		$commanderInvestBonus = $currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::COMMANDER_INVEST);

		$invest = $currentBase->iSchool * $commanderInvestBonus / 100;

		return $this->render('pages/athena/school.html.twig', [
			'commanders' => $commanderRepository->getPlanetCommanders(
				$currentBase,
				[Commander::INSCHOOL],
				['experience' => 'DESC'],
			),
			'reserve_commanders' => $commanderRepository->getPlanetCommanders(
				$currentBase,
				[Commander::RESERVE],
				['experience' => 'DESC'],
			),
			'earned_experience' => $this->calculateEarnedExperience($invest),
			'max_commanders_in_school' => PlaceResource::get($currentBase->typeOfBase, 'school-size'),
			'random_name' => CheckName::randomize(),
			'commander_price' => SchoolClassResource::getInfo(0, 'credit'),
			'commander_invest_bonus' => $commanderInvestBonus,
		]);
	}

	// @TODO Move that logic elsewhere
	private function calculateEarnedExperience(int $invest): int
	{
		return max(round($invest / Commander::COEFFSCHOOL), 0);
	}
}
