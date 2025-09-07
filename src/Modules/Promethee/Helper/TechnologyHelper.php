<?php

namespace App\Modules\Promethee\Helper;

use App\Classes\Container\ArrayList;
use App\Classes\Container\StackList;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Promethee\Resource\TechnologyResource;
use Symfony\Contracts\Service\Attribute\Required;

class TechnologyHelper
{
	protected PlanetHelper $planetHelper;

	public function __construct(
		protected ResearchHelper $researchHelper,
		protected int $researchQuantity,
	) {
	}

	#[Required]
	public function setPlanetHelper(PlanetHelper $planetHelper): void
	{
		$this->planetHelper = $planetHelper;
	}

	public function isATechnology(int $techno): bool
	{
		return \in_array($techno, TechnologyResource::$technologies);
	}

	public function isAnUnblockingTechnology(int $techno): bool
	{
		return \in_array($techno, TechnologyResource::$technologiesForUnblocking);
	}

	public function isATechnologyNotDisplayed(int $techno): bool
	{
		return \in_array($techno, TechnologyResource::$technologiesNotDisplayed);
	}

	public function getInfo($techno, $info, $level = 0)
	{
		if (!$this->isATechnology($techno)) {
			throw new \InvalidArgumentException('Technologie inexistante dans getInfo() de TechnologyResource '.$techno);
		}
		if ($this->isAnUnblockingTechnology($techno)) {
			if (\in_array($info, ['name', 'progName', 'imageLink', 'requiredTechnosphere', 'requiredResearch', 'time', 'resource', 'credit', 'points', 'column', 'shortDescription', 'description'])) {
				return TechnologyResource::$technology[$techno][$info];
			}
			throw new \InvalidArgumentException('2e argument faux pour getInfo() de TechnologyResource (techno '.$techno.', '.$info.')');
		}
		if (in_array($info, ['name', 'progName', 'imageLink', 'requiredTechnosphere', 'requiredResearch', 'maxLevel', 'category', 'column', 'shortDescription', 'description', 'bonus'])) {
			return TechnologyResource::$technology[$techno][$info];
		} elseif (in_array($info, ['time', 'resource', 'credit', 'points'])) {
			if ($level <= 0) {
				return false;
			}
			if ('points' == $info) {
				return round(TechnologyResource::$technology[$techno][$info] * $level * Technology::COEF_POINTS);
			} elseif ('time' == $info) {
				return round(TechnologyResource::$technology[$techno][$info] * $level * Technology::COEF_TIME);
			} else {
				switch (TechnologyResource::$technology[$techno]['category']) {
					case 1:
						$value = round(TechnologyResource::$technology[$techno][$info] * 1.5 ** ($level - 1));
						break;
					case 2:
						$value = round(TechnologyResource::$technology[$techno][$info] * 1.3 ** ($level - 1));
						break;
					case 3:
						$value = round(TechnologyResource::$technology[$techno][$info] * 1.2 ** ($level - 1));
						break;
					default:
						return false;
				}

				//	$value = round($this->technology[$techno][$info] * pow(1.75, $level-1));
				//	$value = round($this->technology[$techno][$info] * pow(1.5, $level-1));
				//	$value = round($this->technology[$techno][$info] * pow(1.3, $level-1));

				return $value;
			}
		}
		throw new \InvalidArgumentException('2e argument faux pour getInof() de TechnologyResource');
	}

	public function haveRights($techno, $type, $arg1 = 0, $arg2 = 'default')
	{
		if ($this->isATechnology($techno)) {
			switch ($type) {
				// assez de ressources pour contruire ?
				// $arg1 est le niveau
				// $arg2 est ce que le joueur possède (ressource ou crédit)
				case 'resource': return $arg2 >= $this->getInfo($techno, 'resource', $arg1);
					break;
				// assez de crédits pour construire ?
				case 'credit': return $arg2 >= $this->getInfo($techno, 'credit', $arg1);
					break;
				// encore de la place dans la queue ?
				// $arg1 est un objet de type OrbitalBase
				// $arg2 est le nombre de technologies dans la queue
				case 'queue':
					$maxQueue = $this->planetHelper->getBuildingInfo(PlanetResource::TECHNOSPHERE, 'level', $arg1->levelTechnosphere, 'nbQueues');

					return $arg2 < $maxQueue;
				// a-t-on le droit de construire ce niveau ?
				// $arg1 est le niveau cible
				case 'levelPermit':
					return $this->isAnUnblockingTechnology($techno)
						? 1 == $arg1
						: $arg1 > 0;
				// est-ce que le niveau de la technosphère est assez élevé ?
				// arg1 est le niveau de la technosphere
				// no break
				case 'technosphereLevel':
					return $this->getInfo($techno, 'requiredTechnosphere') <= $arg1;
				// est-ce que les recherches de l'université sont acquises ?
				// arg1 est le niveau de la technologie
				// arg2 est une stacklist avec les niveaux de recherche
				case 'research':
					$neededResearch = $this->getInfo($techno, 'requiredResearch');
					$researchList = new StackList();
					for ($i = 0; $i < $this->researchQuantity; ++$i) {
						if ($neededResearch[$i] > 0) {
							if ($arg2->get($i) < ($neededResearch[$i] + $arg1 - 1)) {
								$r = new ArrayList();
								$r->add('techno', $this->researchHelper->getInfo($i, 'name'));
								$r->add('level', $neededResearch[$i] + $arg1 - 1);
								$researchList->append($r);
							}
						}
					}
					if ($researchList->size() > 0) {
						return $researchList;
					}
					return true;
					// est-ce qu'on peut construire la techno ? Pas dépassé le niveau max
					// arg1 est le niveau de la technologie voulue
				case 'maxLevel':
					if ($this->isAnUnblockingTechnology($techno)) {
						return true;
					}
					return $arg1 <= $this->getInfo($techno, 'maxLevel');
					// est-ce qu'on peut construire la techno en fonction du type de la base ?
					// arg1 est le type de la base
				case 'baseType':
					return match ($arg1) {
						Planet::BASE_TYPE_COLONY => in_array($this->getInfo($techno, 'column'), [1, 2, 3]),
						Planet::BASE_TYPE_COMMERCIAL => in_array($this->getInfo($techno, 'column'), [1, 2, 3, 4, 5]),
						Planet::BASE_TYPE_MILITARY => in_array($this->getInfo($techno, 'column'), [1, 2, 3, 6, 7]),
						Planet::BASE_TYPE_CAPITAL => in_array($this->getInfo($techno, 'column'), [1, 2, 3, 4, 5, 6, 7]),
						default => false,
					};
				default:
					throw new \RuntimeException('Erreur dans haveRights() de TechnologyResource');
			}
		} else {
			throw new \RuntimeException('Technologie inexistante dans haveRights() de TechnologyResource');
		}
	}

	public function getImprovementPercentage(int $techno, int $level = -1): int
	{
		if ($this->isAnUnblockingTechnology($techno)) {
			return 0;
		}
		$baseBonus = $this->getInfo($techno, 'bonus');
		// TODO explain this calculation and its result
		return match ($level) {
			0 => 0,
			-1 => $baseBonus,
			default => $baseBonus + intval(floor(($level - 1) / 5)),
		};
	}
}
