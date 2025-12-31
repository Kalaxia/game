<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Planet\Building;

use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StorageController extends AbstractController
{
	#[Route(
		path: '/storage',
		name: 'storage',
		methods: [Request::METHOD_GET],
	)]
	public function __invoke(CurrentPlayerBonusRegistry $currentPlayerBonusRegistry): Response
	{
		return $this->render('pages/athena/storage.html.twig', [
			'storage_bonus' => $currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::REFINERY_STORAGE),
		]);
	}
}
