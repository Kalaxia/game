<?php

namespace App\Modules\Zeus\Application\Handler;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Application\Persister\NotificationPersister;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerFinancialReport;

readonly class CommanderWageHandler
{
	public function __construct(
		private CommanderRepositoryInterface $commanderRepository,
		private CommanderManager $commanderManager,
		private NotificationPersister $notificationPersister,
	) {
	}

	/**
	 * @param list<Commander> $commanders
	 */
	public function payWages(PlayerFinancialReport $playerFinancialReport, array $commanders, Player $rebelPlayer): void
	{
		$unpaidCommandersNames = [];
		foreach ($commanders as $commander) {
			if (!in_array($commander->statement, [Commander::AFFECTED, Commander::MOVING])) {
				continue;
			}

			$commanderWage = Commander::LVLINCOMECOMMANDER * $commander->level;

			if ($playerFinancialReport->canAfford($commanderWage)) {
				$playerFinancialReport->commandersWages += $commanderWage;
				continue;
			}
			// TODO what to do with the commander when unable to pay ? Currently selling it
			$this->sellCommander($commander, $rebelPlayer);
			$unpaidCommandersNames[] = $commander->name;
		}
		$unpaidCommandersCount = count($unpaidCommandersNames);
		// si au moins un commandant n'a pas pu être payé --> envoyer une notif
		if ($unpaidCommandersCount > 0) {
			$this->notifyCommanderSale($playerFinancialReport->player, $unpaidCommandersNames, $unpaidCommandersCount);
		}
	}

	private function sellCommander(Commander $commander, Player $rebelPlayer): void
	{
		// on remet les vaisseaux dans les hangars
		$this->commanderManager->emptySquadrons($commander);

		// on vend le commandant
		$commander->statement = Commander::ONSALE;
		$commander->player = $rebelPlayer;

		// TODO : vendre le commandant au marché
		//		(ou alors le mettre en statement COM_DESERT et supprimer ses escadrilles)

		$this->commanderRepository->save($commander);
	}

	private function notifyCommanderSale(Player $player, array $commanderNames, int $commandersCount): void
	{
		$notification = NotificationBuilder::new()
			->setTitle('Commandant impayé')
			->setContent(
				NotificationBuilder::paragraph(
					'Domaine',
					NotificationBuilder::divider(),
					(1 == $commandersCount)
						? sprintf(
							'Vous n\'avez pas assez de crédits pour payer votre commandant %s. Celui-ci a donc déserté !',
							$commanderNames[0],
						)
						: 'Vous n\'avez pas assez de crédits pour payer certains de vos commandants. Ils ont donc déserté !',
				),
				NotificationBuilder::paragraph(
					(1 == $commandersCount)
						? 'Il est allé proposer ses services sur le marché. Si vous voulez le récupérer, vous pouvez vous y rendre et le racheter.'
						: sprintf(
							'Voici la liste de ces commandants : %s.
								Ils sont tous allés proposer leurs services sur le marché.
								Si vous voulez les récupérer, vous pouvez vous y rendre et les racheter.',
							implode(', ', $commanderNames),
						)
				),
			)
			->forPlayer($player);
		$this->notificationPersister->saveFromBuilder($notification);
	}
}
