<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Controller\CombatReport;

use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Model\Report;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class ArchiveReport extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		ReportRepositoryInterface $reportRepository,
		Uuid $id,
	): Response {
		if (($report = $reportRepository->get($id)) !== null) {
			if ($currentPlayer->id === $report->attacker->id) {
				if (Report::STANDARD === $report->attackerStatement) {
					$report->attackerStatement = Report::ARCHIVED;
				} else {
					$report->attackerStatement = Report::STANDARD;
				}
			} elseif ($currentPlayer->id === $report->defender->id) {
				if (Report::STANDARD == $report->defenderStatement) {
					$report->defenderStatement = Report::ARCHIVED;
				} else {
					$report->defenderStatement = Report::STANDARD;
				}
			} else {
				throw new AccessDeniedHttpException('Ce rapport ne vous appartient pas.');
			}
		} else {
			throw new NotFoundHttpException('Ce rapport n\'existe pas.');
		}
		$reportRepository->save($report);

		return $this->redirectToRoute('fleet_archives');
	}
}
