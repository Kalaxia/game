<?php

/**
 * election Manager.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 06.10.13
 */

namespace App\Modules\Demeter\Manager\Election;

use App\Classes\Entity\EntityManager;
use App\Modules\Demeter\Model\Election\Election;

class ElectionManager
{
    public function __construct(protected EntityManager $entityManager)
    {
    }

    /**
     * @param int $id
     *
     * @return Election
     */
    public function get($id)
    {
        return $this->entityManager->getRepository(Election::class)->get($id);
    }

    /**
     * @param int $factionId
     *
     * @return Election
     */
    public function getFactionLastElection($factionId)
    {
        return $this->entityManager->getRepository(Election::class)->getFactionLastElection($factionId);
    }

    /**
     * @return int
     */
    public function add(Election $election)
    {
        $this->entityManager->persist($election);
        $this->entityManager->flush($election);

        return $election->id;
    }
}
