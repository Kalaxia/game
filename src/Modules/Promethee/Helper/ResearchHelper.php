<?php

namespace App\Modules\Promethee\Helper;

use App\Modules\Promethee\Model\Research;
use App\Modules\Promethee\Resource\ResearchResource;

readonly class ResearchHelper
{
	public function __construct(
		private float $researchCoeff,
		private int $researchMaxDiff,
	) {
	}

	public function isAResearch(int $research): bool
	{
		return in_array($research, ResearchResource::$availableResearch);
	}

	public function getInfo($research, $info, $level = 0, $sup = 'delfault')
	{
		if ($this->isAResearch($research)) {
			if ('name' == $info || 'codeName' == $info) {
				return ResearchResource::$research[$research][$info];
			} elseif ('level' == $info) {
				if ($level <= 0) {
					return false;
				}
				if ('price' == $sup) {
					return intval(round($this->researchPrice($research, $level) * $this->researchCoeff));
				}
			} else {
				throw new \RuntimeException('Wrong second argument for method getInfo() from ResearchResource');
			}
		} else {
			throw new \RuntimeException('This research doesn\'t exist !');
		}

		return false;
	}

	public function isResearchPermit(int $firstLevel, int $secondLevel, int $thirdLevel = -1): bool
	{
		// compare the levels of technos and say if you can research such techno
		if (-1 == $thirdLevel) {
			if (abs($firstLevel - $secondLevel) > $this->researchMaxDiff) {
				return false;
			}

			return true;
		}
		if (abs($firstLevel - $secondLevel) > $this->researchMaxDiff) {
			return false;
		} elseif (abs($firstLevel - $thirdLevel) > $this->researchMaxDiff) {
			return false;
		} elseif (abs($secondLevel - $thirdLevel) > $this->researchMaxDiff) {
			return false;
		}

		return true;
	}

	public function researchPrice(int $research, int $level): int
	{
		return (1 === $level)
			? $this->getDefaultResearchPrice($research)
			: $this->calculateLevelPrice($level);
	}

	private function calculateLevelPrice(int $level): int
	{
		return intval(round(
			(0.0901 * $level ** 5)
			- (12.988 * $level ** 4)
			+ (579.8 * $level ** 3)
			- (5735.8 * $level ** 2)
			+ (28259 * $level)
			- 25426
		));
	}

	private function getDefaultResearchPrice(int $research): int
	{
		return match ($research) {
			Research::MATH => 100,
			Research::PHYS => 3000,
			Research::CHEM => 7000,
			Research::LAW, Research::ECONO, Research::NETWORK => 200,
			Research::COMM, Research::PSYCHO => 9000,
			Research::ALGO => 4000,
			Research::STAT => 6000,
			default => throw new \RuntimeException(sprintf('Invalid research identifier given: %d', $research)),
		};
	}
}
