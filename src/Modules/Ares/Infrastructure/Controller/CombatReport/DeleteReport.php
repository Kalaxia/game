<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Controller\CombatReport;

use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Model\Report;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class DeleteReport extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ReportRepositoryInterface $reportRepository,
		Uuid $id,
	): Response {
		$report = $reportRepository->get($id)
			?? throw $this->createNotFoundException('Ce rapport n\'existe pas');

		if ($report->attacker->id === $currentPlayer->id) {
			$report->attackerStatement = Report::DELETED;
		} elseif ($report->defender->id === $currentPlayer->id) {
			$report->defenderStatement = Report::DELETED;
		} else {
			throw $this->createAccessDeniedException('Ce rapport ne vous appartient pas');
		}

		$reportRepository->save($report);

		return $this->redirectToRoute('fleet_archives');
	}
}
