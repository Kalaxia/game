<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Handler;

use App\Modules\Ares\Domain\Model\Battle\OfficerTrait;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Zeus\Manager\PlayerManager;
use Random\Randomizer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class CommanderExperienceHandler
{
	/** Levels at which an officer gets a new trait, drawn at random (D20) */
	public const array TRAIT_LEVELS = [5, 10, 15];

	public function __construct(
		private CommanderRepositoryInterface $commanderRepository,
		private PlayerManager $playerManager,
		#[Autowire('%ares.commander.base_level%')]
		private int $commanderBaseLevel,
		private Randomizer $randomizer = new Randomizer(),
	) {
	}

	/**
	 * Experience earned in a battle (D19): the officer gets it all, their player a hundredth of it.
	 */
	public function awardBattleExperience(Commander $commander, int $experience): void
	{
		$commander->earnedExperience = $experience;

		if (null !== $commander->player) {
			$this->playerManager->increaseExperience($commander->player, intval(round($experience / Commander::COEFFEXPPLAYER)));
		}

		$this->upExperience($commander, $experience);
	}

	/**
	 * @param bool $drawTraits false for the experience of a new recruit: a trait at recruitment would make
	 *                         players dismiss officers until they get the one they want (D20)
	 */
	public function upExperience(Commander $commander, int $earnedExperience, bool $drawTraits = true): void
	{
		$commander->experience += $earnedExperience;

		while ($commander->experience >= $this->experienceToLevelUp($commander)) {
			++$commander->level;

			if ($drawTraits && in_array($commander->level, self::TRAIT_LEVELS, true)) {
				$this->drawTrait($commander);
			}
		}

		$this->commanderRepository->save($commander);
	}

	public function nbLevelUp(int $level, int $newExperience): int
	{
		$oLevel = $level;
		$nLevel = $level;
		while (1) {
			if ($newExperience >= (2 ** $nLevel * $this->commanderBaseLevel)) {
				++$nLevel;
			} else {
				break;
			}
		}

		return $nLevel - $oLevel;
	}

	public function experienceToLevelUp(Commander $commander): int
	{
		return intval(2 ** $commander->level * $this->commanderBaseLevel);
	}

	private function drawTrait(Commander $commander): void
	{
		$candidates = array_values(array_filter(
			OfficerTrait::cases(),
			fn (OfficerTrait $trait) => !in_array($trait->value, $commander->traits, true),
		));

		if (count($commander->traits) >= OfficerTrait::MAX_PER_OFFICER || [] === $candidates) {
			return;
		}

		$commander->traits[] = $candidates[$this->randomizer->getInt(0, count($candidates) - 1)]->value;
	}
}
