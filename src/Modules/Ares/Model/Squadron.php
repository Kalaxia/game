<?php

/**
 * Squadron.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 13.02.14
 */

namespace App\Modules\Ares\Model;

use App\Modules\Ares\Manager\FightManager;

class Squadron
{
    public $id = 0;

    public $lineCoord = 0;
    public $nbrShips = 0;
    public $rCommander = 0;
    public $position = 0; // position dans le tableau de l'armée
    public $arrayOfShips = [];
    public $relId = 0;
    public $dLastModification = '';

    public $squadron = [];

    // GETTER

    public function getId()
    {
        return $this->id;
    }

    public function getLineCoord()
    {
        return $this->lineCoord;
    }

    public function getNbrShips()
    {
        return $this->nbrShips;
    }

    public function getRCommander()
    {
        return $this->rCommander;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getSquadron()
    {
        return $this->squadron;
    }

    public function getArrayOfShips()
    {
        return $this->arrayOfShips;
    }

    public function getDLastModification()
    {
        return $this->dLAstModification;
    }

    public function getShip($key)
    {
        return $this->squadron[$key];
    }

    public function getNbrShipByType($i)
    {
        return $this->arrayOfShips[$i];
    }

    public function getPev()
    {
        $pev = 0;
        foreach ($this->squadron as $ship) {
            $pev += $ship->getPev();
        }

        return $pev;
    }

    private function getPv()
    {
        $pv = 0;
        foreach ($this->squadron as $ship) {
            $pv += $ship->getLife();
        }

        return $pv;
    }

    private function howManyLostPv($squadron1, $squadron2)
    {
        $lostPv = abs($squadron1->getPv() - $squadron2->getPv());

        return $lostPv;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function isEmpty()
    {
        if (0 == $this->getPev()) {
            return true;
        } else {
            return false;
        }
    }

    public function setRelId($id)
    {
        $this->relId = $id;
    }

    public function __construct($vector, $id, $lineCoord, $position, $rCommander, $isAttacker = null)
    {
        for ($i = 0; $i < 12; ++$i) {
            $this->arrayOfShips[] = $vector[$i];
        }

        $this->dLastModification = $vector[12];
        $this->lineCoord = $lineCoord;
        $this->id = $id;
        $this->rCommander = $rCommander;
        $this->position = $position;

        for ($i = 0; $i < 12; ++$i) {
            for ($j = 0; $j < $vector[$i]; ++$j) {
                $this->squadron[] = new Ship($i, $isAttacker);
                $this->squadron[$this->nbrShips]->affectId($this->nbrShips);
                ++$this->nbrShips;
            }
        }
    }

    public function updateShip($shipNbrName, $nbr)
    {
        $this->arrayOfShips[$shipNbrName] += $nbr;
    }

    public function emptySquadron()
    {
        for ($i = 0; $i < 12; ++$i) {
            $this->arrayOfShips[$i] = 0;
        }
    }

    // méthodes de combat
    public function engage($enemyCommander, $position, $idCommander, $nameCommander, $thisCommander)
    {
        $this->relId = $this->chooseEnemy($enemyCommander);
        if (null !== $this->relId) {
            $thisSquadronBefore = $this;
            $enemySquadron = $enemyCommander->getSquadron($this->relId);

            ++LiveReport::$littleRound;
            $this->fight($enemyCommander->getSquadron($this->relId));
            LiveReport::$squadrons[] = [0, $this->position, 0, LiveReport::$littleRound, $this->rCommander, $this->arrayOfShips[0], $this->arrayOfShips[1], $this->arrayOfShips[2], $this->arrayOfShips[3], $this->arrayOfShips[4], $this->arrayOfShips[5], $this->arrayOfShips[6], $this->arrayOfShips[7], $this->arrayOfShips[8], $this->arrayOfShips[9], $this->arrayOfShips[10], $this->arrayOfShips[11]];
            LiveReport::$squadrons[] = [0, $enemySquadron->position, 0, LiveReport::$littleRound, $enemySquadron->rCommander, $enemySquadron->arrayOfShips[0], $enemySquadron->arrayOfShips[1], $enemySquadron->arrayOfShips[2], $enemySquadron->arrayOfShips[3], $enemySquadron->arrayOfShips[4], $enemySquadron->arrayOfShips[5], $enemySquadron->arrayOfShips[6], $enemySquadron->arrayOfShips[7], $enemySquadron->arrayOfShips[8], $enemySquadron->arrayOfShips[9], $enemySquadron->arrayOfShips[10], $enemySquadron->arrayOfShips[11]];

            $enemyCommander->getSquadron($this->relId)->setRelId($this->position);
            $enemyCommander->getSquadron($this->relId)->fight($thisCommander->getSquadron($this->position));
        }

        return $enemyCommander;
    }

    private function chooseEnemy(Commander $enemyCommander)
    {
        $nbrShipsInLine = 0;
        foreach ($enemyCommander->getArmy() as $enemySquadron) {
            if ($enemySquadron->getLineCoord() * 3 <= FightManager::getCurrentLine()) {
                $nbrShipsInLine += $enemySquadron->getNbrShips();
            }
        }
        if (0 == $nbrShipsInLine) {
            return null;
        } elseif (null != $this->relId and $enemyCommander->getSquadron($this->relId)->getNbrShips() > 0) {
            return $this->relId;
        } else {
            $aleaNbr = rand(0, count($enemyCommander->squadronsIds) - 1);
            for ($i = 0; $i < $enemyCommander->getLevel(); ++$i) {
                if ($enemyCommander->getSquadron($aleaNbr)->getLineCoord() * 3 <= FightManager::getCurrentLine() and $enemyCommander->getSquadron($aleaNbr)->getNbrShips() > 0) {
                    break;
                } else {
                    if ($aleaNbr == count($enemyCommander->squadronsIds) - 1) {
                        $aleaNbr = 0;
                    } else {
                        ++$aleaNbr;
                    }
                }
            }

            return $aleaNbr;
        }
    }

    public function fight($enemySquadron)
    {
        foreach ($this->squadron as $ship) {
            if (0 == $enemySquadron->getNbrShips()) {
                break;
            }
            $enemySquadron = $ship->engage($enemySquadron);
        }
    }

    public function destructShip($key)
    {
        $this->deleteOffset($this->squadron[$key]->getNbrName());

        $this->squadron[$key] = null;
        $newSquadron = [];
        foreach ($this->squadron as $offset) {
            if (null != $offset) {
                $newSquadron[] = $offset;
            }
        }
        $this->squadron = $newSquadron;
    }

    private function deleteOffset($i)
    {
        --$this->arrayOfShips[$i];
        --$this->nbrShips;
    }
}
