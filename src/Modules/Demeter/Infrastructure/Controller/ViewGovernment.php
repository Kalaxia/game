<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Domain\Repository\Forum\FactionNewsRepositoryInterface;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\CreditTransactionRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsGovernmentMember;
use App\Modules\Zeus\Infrastructure\Validator\IsParliamentMember;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class ViewGovernment extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CreditTransactionRepositoryInterface $creditTransactionRepository,
		ColorManager $colorManager,
		PlayerRepositoryInterface $playerRepository,
		SectorRepositoryInterface $sectorRepository,
		FactionNewsRepositoryInterface $factionNewsRepository,
	): Response {
		if (!$currentPlayer->isGovernmentMember()) {
			throw $this->createAccessDeniedException();
		}
		$faction = $currentPlayer->faction;

		return $this->render('pages/demeter/faction/government.html.twig', [
			'faction' => $faction,
			'credit_transactions' => $creditTransactionRepository->getAllBySender($faction),
			'senators' => $playerRepository->getBySpecification(new IsParliamentMember($faction)),
			'faction_sectors' => $sectorRepository->getFactionSectors($faction),
			'faction_news' => $factionNewsRepository->getFactionNews($faction),
			'faction_news_to_edit' => $request->query->has('news') ? $factionNewsRepository->get(Uuid::fromString($request->query->get('news'))) : null,
			'faction_members' => $playerRepository->getFactionPlayersByName($faction),
			'members_count' => $playerRepository->countByFactionAndStatements($faction, [Player::ACTIVE]),
			'government_members' => $playerRepository->getBySpecification(new IsGovernmentMember($faction)),
			'total_laws_count' => LawResources::size(),
		]);
	}
}
