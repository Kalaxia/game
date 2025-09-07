<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Modules\Artemis\Domain\Repository\SpyReportRepositoryInterface;
use App\Modules\Galaxy\Manager\PlaceManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class ViewSpyReports extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		PlaceManager $placeManager,
		SpyReportRepositoryInterface $spyReportRepository,
	): Response {
		$spyReports = $spyReportRepository->getPlayerReports($currentPlayer);

		$focusedSpyReport = null;

		if ($request->query->has('report')) {
			$focusedSpyReport = $spyReportRepository->get(
				Uuid::fromString($request->query->get('report'))
			);
		}
		if (null === $focusedSpyReport || $focusedSpyReport->player->id !== $currentPlayer->id) {
			$focusedSpyReport = $spyReports[0] ?? null;
		}

		return $this->render('pages/ares/fleet/spy_reports.html.twig', [
			'spy_reports' => $spyReports,
			'focused_spy_report' => $focusedSpyReport,
		]);
	}
}
