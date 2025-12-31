<?php

namespace App\Modules\Athena\Infrastructure\Controller\Planet\Building;

use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RefineryController extends AbstractController
{
	#[Route(
		path: '/refinery',
		name: 'refinery',
		methods: [Request::METHOD_GET],
	)]
	public function __invoke(
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
	): Response {
		return $this->render('pages/athena/refinery.html.twig', [
			'refining_bonus' => $currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::REFINERY_REFINING),
		]);
	}
}
