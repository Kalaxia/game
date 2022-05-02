<?php

/**
 * VoteLawLaw Manager.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 29.09.14
 */

namespace App\Modules\Demeter\Manager\Law;

use App\Classes\Entity\EntityManager;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Model\Law\VoteLaw;

class VoteLawManager
{
	public function __construct(protected EntityManager $entityManager)
	{
	}

	/**
	 * @return int
	 */
	public function add(VoteLaw $voteLaw)
	{
		$this->entityManager->persist($voteLaw);
		$this->entityManager->flush($voteLaw);

		return $voteLaw->id;
	}

	/**
	 * @return array
	 */
	public function getLawVotes(Law $law)
	{
		return $this->entityManager->getRepository(VoteLaw::class)->getLawVotes($law->id);
	}

	/**
	 * @param int $playerId
	 *
	 * @return bool
	 */
	public function hasVoted($playerId, Law $law)
	{
		return $this->entityManager->getRepository(VoteLaw::class)->hasVoted($playerId, $law->id);
	}
}
