<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Modules\Ares\Domain\Repository\BattleRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Military archives: the battles the player took part in, each with its report and replay (Q15).
 */
class ViewArchives extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		BattleRepositoryInterface $battleRepository,
	): Response {
		return $this->render('pages/ares/fleet/archives.html.twig', [
			'battles' => $battleRepository->getPlayerBattles($currentPlayer),
		]);
	}
}
