<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Resource\PlaceResource;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Helper\CheckName;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewSchool extends AbstractController
{
	public function __invoke(
		Request $request,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		OrbitalBase $currentBase,
		CommanderRepositoryInterface $commanderRepository,
		#[Autowire('%app.commander_school.credits_cost%')]
		int $creditsCost,
	): Response {
		$commanderInvestBonus = $currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::COMMANDER_INVEST);

		$invest = $currentBase->iSchool * $commanderInvestBonus / 100;

		return $this->render('pages/athena/school.html.twig', [
			'commanders' => $commanderRepository->getBaseCommanders(
				$currentBase,
				[Commander::INSCHOOL],
				['experience' => 'DESC'],
			),
			'reserve_commanders' => $commanderRepository->getBaseCommanders(
				$currentBase,
				[Commander::RESERVE],
				['experience' => 'DESC'],
			),
			'earned_experience' => $this->calculateEarnedExperience($invest),
			'max_commanders_in_school' => PlaceResource::get($currentBase->typeOfBase, 'school-size'),
			'random_name' => CheckName::randomize(),
			'commander_price' => $creditsCost,
			'commander_invest_bonus' => $commanderInvestBonus,
		]);
	}

	// @TODO Move that logic elsewhere
	private function calculateEarnedExperience(int $invest): int
	{
		return max(round($invest / Commander::COEFFSCHOOL), 0);
	}
}
