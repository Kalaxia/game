<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Library\Format;
use App\Modules\Atlas\Domain\Repository\FactionRankingRepositoryInterface;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\Forum\FactionNewsRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Manager\Forum\FactionNewsManager;
use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Galaxy\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsGovernmentMember;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewOverview extends AbstractController
{
	public function __construct(
		private readonly FactionRankingRepositoryInterface $factionRankingRepository,
		private readonly NextElectionDateCalculator $nextElectionDateCalculator,
		private readonly DurationHandler $durationHandler,
	) {
	}

	public function __invoke(
		Request $request,
		Player $currentPlayer,
		FactionNewsManager $factionNewsManager,
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

		$data = [
			'faction_ranking' => $this->factionRankingRepository->getLastRanking($faction),
			'faction' => $faction,
			'news' => $factionNews,
			'news_mode' => $mode,
			'government_members' => $playerRepository->getBySpecification(new IsGovernmentMember($faction)),
			'effective_laws' => $lawRepository->getByFactionAndStatements($faction, [Law::EFFECTIVE]),
			'voting_laws' => $lawRepository->getByFactionAndStatements($faction, [Law::VOTATION]),
			'sectors_count' => $sectorRepository->countFactionSectors($faction),
			'active_players_count' => $playerRepository->countByFactionAndStatements($faction, [Player::ACTIVE]),
		];

		if ($faction->hasElections()) {
			$data = array_merge($data, $this->getElectionsData($faction));
		} elseif ($faction->isInElection()) {
			$endPutsch = $this->nextElectionDateCalculator->getPutschEndDate($faction);

			$data['remaining_coup_time'] = $this->durationHandler->getRemainingTime($endPutsch);
		}

		return $this->render('pages/demeter/faction/overview.html.twig', $data);
	}

	private function getElectionsData(Color $faction): array
	{
		// time variables
		$startCampaign = $this->nextElectionDateCalculator->getCampaignStartDate($faction);
		$endCampaign = $this->nextElectionDateCalculator->getCampaignEndDate($faction);
		$endElection = $this->nextElectionDateCalculator->getBallotDate($faction);

		$startMandate = $this->nextElectionDateCalculator->getNextElectionDate($faction);
		$endMandate = $faction->isDemocratic()
			? $endElection
			: $endCampaign;

		$now = new \DateTimeImmutable();
		$totalCampaignElection = $this->durationHandler->getDiff($startCampaign, $endElection);
		$remainingCampaignElection = $this->durationHandler->getDiff($now, $startCampaign);

		// @TODO Rename these keys to give more meaning
		return [
			'total_mandate' => $this->durationHandler->getDiff($startMandate, $endMandate),
			'remaining_mandate' => $this->durationHandler->getDiff($startMandate, $now),
			'total_before_campaign' => $this->durationHandler->getDiff($startMandate, $startCampaign),
			'total_campaign_election' => $totalCampaignElection,
			'total_campaign' => $this->durationHandler->getDiff($startCampaign, $endCampaign),
			'remaining_campaign_election' => $remainingCampaignElection,
			'election_progress' => $now > $startCampaign
				? Format::percent($remainingCampaignElection, $totalCampaignElection, false)
				: '0',
		];
	}
}
