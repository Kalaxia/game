<?php

/**
 * Candidate Manager.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 06.10.13
 */

namespace App\Modules\Demeter\Manager\Election;

use App\Classes\Entity\EntityManager;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Zeus\Model\Player;

class CandidateManager
{
    public function __construct(protected EntityManager $entityManager)
    {
    }

    /**
     * @param int $id
     *
     * @return Candidate
     */
    public function get($id)
    {
        return $this->entityManager->getRepository(Candidate::class)->get($id);
    }

    /**
     * @return array
     */
    public function getByElection(Election $election)
    {
        return $this->entityManager->getRepository(Candidate::class)->getByElection($election->id);
    }

    /**
     * @return Candidate
     */
    public function getByElectionAndPlayer(Election $election, Player $player)
    {
        return $this->entityManager->getRepository(Candidate::class)->getByElectionAndPlayer($election->id, $player->id);
    }

    /**
     * @return int
     */
    public function add(Candidate $candidate)
    {
        $this->entityManager->persist($candidate);
        $this->entityManager->flush($candidate);

        return $candidate->id;
    }
}
