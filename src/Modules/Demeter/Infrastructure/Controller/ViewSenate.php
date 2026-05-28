<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Zeus\Domain\Enum\PlayerStatement;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewSenate extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		PlayerRepositoryInterface $playerRepository,
		LawRepositoryInterface $lawRepository,
	): Response {
		if (!$currentPlayer->isParliamentMember()) {
			throw $this->createAccessDeniedException('You must be a parliament member');
		}

		return $this->render('pages/demeter/faction/senate.html.twig', [
			'faction' => $currentPlayer->faction,
			'active_players_count' => $playerRepository->countByFactionAndStatements($currentPlayer->faction, [PlayerStatement::Active]),
			'voting_laws' => $lawRepository->getByFactionAndStatements($currentPlayer->faction, [Law::VOTATION]),
			'voted_laws' => $lawRepository->getByFactionAndStatements($currentPlayer->faction, [Law::EFFECTIVE, Law::OBSOLETE, Law::REFUSED]),
		]);
	}
}
