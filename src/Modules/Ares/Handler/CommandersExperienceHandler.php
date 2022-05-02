<?php

namespace App\Modules\Ares\Handler;

use App\Classes\Entity\EntityManager;
use App\Classes\Library\Utils;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Message\CommandersExperienceMessage;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CommandersExperienceHandler implements MessageHandlerInterface
{
	public function __construct(
		protected EntityManager $entityManager,
		protected OrbitalBaseManager $orbitalBaseManager,
		protected PlayerBonusManager $playerBonusManager,
		protected PlayerManager $playerManager,
		protected CommanderManager $commanderManager,
	) {
	}

	public function __invoke(CommandersExperienceMessage $message): void
	{
		$now = Utils::now();
		$commanders = $this->entityManager->getRepository(Commander::class)->getAllByStatements([Commander::INSCHOOL]);
		$this->entityManager->beginTransaction();

		foreach ($commanders as $commander) {
			// If the commander was updated recently, we skip him
			if (0 === Utils::interval($commander->uCommander, $now, 'h')) {
				continue;
			}

			$nbrHours = Utils::intervalDates($now, $commander->uCommander);
			$commander->uCommander = $now;
			$orbitalBase = $this->orbitalBaseManager->get($commander->rBase);

			$playerBonus = $this->playerBonusManager->getBonusByPlayer($this->playerManager->get($commander->rPlayer));
			$playerBonus = $playerBonus->bonuses;
			foreach ($nbrHours as $hour) {
				$invest = $orbitalBase->iSchool;
				$invest += $invest * $playerBonus->get(PlayerBonusId::COMMANDER_INVEST) / 100;

				// xp gagnée
				$earnedExperience = $invest / Commander::COEFFSCHOOL;
				$earnedExperience += (1 == rand(0, 1))
					? rand(0, intval(round($earnedExperience / 20)))
					: -(rand(0, intval(round($earnedExperience / 20))));
				$earnedExperience = max(round($earnedExperience), 0);

				$this->commanderManager->upExperience($commander, $earnedExperience);
			}
		}
		$this->entityManager->commit();
	}
}
