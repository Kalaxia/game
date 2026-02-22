<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Atlas\Domain\Repository\FactionRankingRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Forum\FactionNewsRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Manager\Forum\FactionNewsManager;
use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsGovernmentMember;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewOverview extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		FactionNewsManager $factionNewsManager,
		FactionRankingRepositoryInterface $factionRankingRepository,
		FactionNewsRepositoryInterface $factionNewsRepository,
		PlayerRepositoryInterface $playerRepository,
		LawManager $lawManager,
		LawRepositoryInterface $lawRepository,
		SectorRepositoryInterface $sectorRepository,
	): Response {
		$faction = $currentPlayer->faction;
		if ('list' === $request->query->get('news')) {
			$factionNews = $factionNewsRepository->getFactionBasicNews($faction);
			$mode = 'all';
		} else {
			$factionNews = $factionNewsRepository->getPinnedNew($faction);
			$mode = 'pin';
		}

		return $this->render('pages/demeter/faction/overview.html.twig', [
			'faction_ranking' => $factionRankingRepository->getLastRanking($faction),
			'faction' => $faction,
			'news' => $factionNews,
			'news_mode' => $mode,
			'government_members' => $playerRepository->getBySpecification(new IsGovernmentMember($faction)),
			'effective_laws' => $lawRepository->getByFactionAndStatements($faction, [Law::EFFECTIVE]),
			'voting_laws' => $lawRepository->getByFactionAndStatements($faction, [Law::VOTATION]),
			'sectors_count' => $sectorRepository->countFactionSectors($faction),
			'active_players_count' => $playerRepository->countByFactionAndStatements($faction, [Player::ACTIVE]),
		]);
	}
}
