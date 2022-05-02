<?php

/**
 * Report Manager.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 12.07.13
 */

namespace App\Modules\Ares\Manager;

use App\Classes\Entity\EntityManager;
use App\Modules\Ares\Model\Report;

class ReportManager
{
	public function __construct(protected EntityManager $entityManager)
	{
	}

	/**
	 * @param int $id
	 *
	 * @return Report
	 */
	public function get($id)
	{
		return $this->entityManager->getRepository(Report::class)->get($id);
	}

	/**
	 * @param int    $attackerId
	 * @param int    $placeId
	 * @param string $dFight
	 *
	 * @return array
	 */
	public function getByAttackerAndPlace($attackerId, $placeId, $dFight)
	{
		return $this->entityManager->getRepository(Report::class)->getByAttackerAndPlace($attackerId, $placeId, $dFight);
	}

	public function add(Report $report)
	{
		$this->entityManager->persist($report);
		$this->entityManager->flush($report);
	}

	/**
	 * @param int $playerId
	 */
	public function removePlayerReports($playerId)
	{
		$this->entityManager->getRepository(Report::class)->removePlayerReports($playerId);
	}
}
