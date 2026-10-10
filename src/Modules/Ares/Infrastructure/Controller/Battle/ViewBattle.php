<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Controller\Battle;

use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Ares\Infrastructure\Battle\CanWatchBattle;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * A battle of the game: followed live while it goes on, then its report and replay (Q15, Q17).
 */
#[Route(
	path: '/battles/{id}',
	name: 'battle_view',
	requirements: ['id' => '[0-9a-f-]{36}'],
	methods: ['GET'],
)]
class ViewBattle extends AbstractController
{
	public function __invoke(
		Uuid $id,
		Player $currentPlayer,
		BattleRepositoryInterface $battleRepository,
		CanWatchBattle $canWatchBattle,
	): Response {
		$battle = $battleRepository->get($id);

		if (null === $battle || !$canWatchBattle($battle, $currentPlayer)) {
			throw new NotFoundHttpException('Battle not found');
		}

		return $this->render('pages/ares/fleet/battle.html.twig', [
			'battle' => $battle,
		]);
	}
}
