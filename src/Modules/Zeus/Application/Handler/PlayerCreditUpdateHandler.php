<?php

namespace App\Modules\Zeus\Application\Handler;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Handler\Income\CommercialRouteIncomeHandler;
use App\Modules\Athena\Application\Handler\Tax\PopulationTaxHandler;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Shared\Application\Service\CountMissingSystemUpdates;
use App\Modules\Shared\Domain\Service\GameTimeConverter;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Domain\Message\PlayerCreditUpdateMessage;
use App\Modules\Zeus\Domain\Repository\PlayerFinancialReportRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerFinancialReport;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class PlayerCreditUpdateHandler
{
	private const int MAX_UPDATES_TO_HANDLE = 5;
	private const int MAX_MISSING_UPDATES = 24;

	public function __construct(
		private EntityManagerInterface $entityManager,
		private CommercialRouteIncomeHandler $commercialRouteIncomeHandler,
		private CommercialRouteConstructionReportHandler $commercialRouteConstructionReportHandler,
		private CommanderRepositoryInterface $commanderRepository,
		private CreditTransactionReportHandler $creditTransactionReportHandler,
		private GameTimeConverter $gameTimeConverter,
		private PlanetRepositoryInterface $planetRepository,
		private PlayerRepositoryInterface $playerRepository,
		private PlayerFinancialReportRepositoryInterface $playerFinancialReportRepository,
		private PlayerTransactionReportHandler $playerTransactionReportHandler,
		private PlayerBonusManager $playerBonusManager,
		private PopulationTaxHandler $populationTaxHandler,
		private CommanderWageHandler $commanderWageHandler,
		private MessageBusInterface $messageBus,
		private ShipsWageHandler $shipsWageHandler,
		private UniversityInvestmentHandler $universityInvestmentHandler,
		private LoggerInterface $logger,
		private TechnologyInvestmentReportHandler $technologyInvestmentReportHandler,
		private CurrentPlayerRegistry $currentPlayerRegistry,
		private CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		private CountMissingSystemUpdates $countMissingSystemUpdates,
		private RecycledCreditsReportHandler $recycledCreditsReportHandler,
		private int $galaxyId,
	) {
	}

	public function __invoke(PlayerCreditUpdateMessage $message): void
	{
		$player = $this->playerRepository->get($message->getPlayerId())
			?? throw new \RuntimeException('Player not found');
		$rebelPlayer = $this->playerRepository->get($this->galaxyId);
		$bases = $this->planetRepository->getPlayerPlanets($player);
		$commanders = $this->commanderRepository->getPlayerCommanders(
			$player,
			[Commander::AFFECTED, Commander::MOVING],
			['c.experience' => 'DESC', 'c.statement' => 'ASC'],
		);
		$initialCredits = $player->credit;

		$missingUpdatesCount = ($this->countMissingSystemUpdates)($player);
		if (0 === $missingUpdatesCount) {
			$this->logger->debug('No updates to perform on player {playerName}', [
				'playerName' => $player->name,
				'playerId' => $player->id,
			]);

			return;
		}

		$this->logger->debug('{count} missing updates for player {playerName}', [
			'count' => $missingUpdatesCount,
			'playerName' => $player->name,
			'playerId' => $player->id,
		]);

		$this->currentPlayerRegistry->set($player);
		$bonus = $this->playerBonusManager->getBonusByPlayer($player);
		$this->currentPlayerBonusRegistry->setPlayerBonus($bonus);
		$secondsPerGameCycle = $this->gameTimeConverter->convertGameCyclesToSeconds(1);

		$lastFinancialReport = $this->playerFinancialReportRepository->getPlayerLastReport($player);

		if (null !== $lastFinancialReport) {
			$this->logger->debug('Last financial report for player {playerName} was created at {createdAt}', [
				'playerName' => $player->name,
				'playerId' => $player->id,
				'createdAt' => $lastFinancialReport->createdAt,
			]);
		}

		// The first time, we make a report for all missing game cycle updates above the limit
		$secondsToAdd = ($missingUpdatesCount > self::MAX_MISSING_UPDATES)
			? $secondsPerGameCycle * ($missingUpdatesCount - self::MAX_MISSING_UPDATES)
			: $secondsPerGameCycle;

		$this->entityManager->beginTransaction();

		try {
			$launchNewMessage = false;

			for ($i = 0; $i < $missingUpdatesCount; ++$i) {
				if (self::MAX_UPDATES_TO_HANDLE === $i) {
					$launchNewMessage = true;

					break;
				}

				$createdAt = (null !== $lastFinancialReport)
					? (clone $lastFinancialReport->createdAt)->modify(sprintf('+%d seconds', $secondsToAdd))
					: new \DateTimeImmutable();

				$playerFinancialReport = new PlayerFinancialReport(
					id: Uuid::v4(),
					player: $player,
					createdAt: $createdAt,
					initialWallet: $player->credit,
				);

				$this->logger->debug('Processing new financial report for player {playerName} with an initial wallet of {credits} credits', [
					'playerName' => $player->name,
					'playerId' => $player->id,
					'credits' => $playerFinancialReport->initialWallet,
					'createdAt' => $playerFinancialReport->createdAt,
				]);

				$this->updatePlayerCredits(
					$playerFinancialReport,
					$lastFinancialReport,
					$rebelPlayer,
					$commanders,
					$bases,
				);

				$player->credit = $playerFinancialReport->getNewWallet();
				$this->entityManager->persist($playerFinancialReport);

				$lastFinancialReport = $playerFinancialReport;
				// For the next iteration, we do back to one game cycle per report
				$secondsToAdd = $secondsPerGameCycle;
			}

			$player->uPlayer = $lastFinancialReport->createdAt;

			$this->entityManager->flush();
			$this->entityManager->commit();

			$this->logger->debug('{playerName} has passed from {credits} to {newCredits} credits', [
				'playerName' => $player->name,
				'playerId' => $player->id,
				'credits' => $initialCredits,
				'newCredits' => $player->credit,
				'updatedAt' => $player->uPlayer,
			]);

			if (true === $launchNewMessage) {
				$this->messageBus->dispatch(new PlayerCreditUpdateMessage($player->id));

				$this->logger->debug('Dispatched new update message for the next iterations for player {playerName}', [
					'playerName' => $player->name,
					'playerId' => $player->id,
				]);
			}
		} catch (\Throwable $e) {
			$this->entityManager->rollback();

			throw $e;
		}
	}

	/**
	 * TODO call a Symfony service iterator instead of all explicit calls here.
	 *
	 * @param list<Planet> $bases
	 */
	private function updatePlayerCredits(
		PlayerFinancialReport $playerFinancialReport,
		?PlayerFinancialReport $lastFinancialReport,
		Player $rebelPlayer,
		array $commanders,
		array $bases,
	): void {
		$player = $playerFinancialReport->player;
		// Process all player bases income and losses
		foreach ($bases as $base) {
			$populationTax = $this->populationTaxHandler->getPopulationTax($base)->getTotal();

			$routesIncome = $this->commercialRouteIncomeHandler->getCommercialRouteIncome($base)->total;

			$factionTax = $this->payFactionTax($base, $populationTax, $playerFinancialReport);

			$playerFinancialReport->populationTaxes += $populationTax;
			$playerFinancialReport->commercialRoutesIncome += $routesIncome;
			// TODO Handler anti spy and school investment recomputing in case of bankrupt
			$playerFinancialReport->antiSpyInvestments += $base->iAntiSpy;
			$playerFinancialReport->schoolInvestments += $base->iSchool;

			$this->logger->debug('Processed income and taxes for base {baseName} of player {playerName}', [
				'playerId' => $player->id,
				'playerName' => $player->name,
				'baseName' => $base->name,
				'populationTaxes' => $populationTax,
				'commercialRoutesIncome' => $routesIncome,
				'antiSpyInvestments' => $base->iAntiSpy,
				'schoolInvestments' => $base->iSchool,
				'factionTax' => $factionTax,
			]);
		}

		($this->playerTransactionReportHandler)($playerFinancialReport, $lastFinancialReport);

		$this->logger->debug('Processed transactions report for player {playerName}', [
			'playerId' => $player->id,
			'playerName' => $player->name,
			'resourcesSales' => $playerFinancialReport->resourcesSales,
			'shipsSales' => $playerFinancialReport->shipsSales,
			'commandersSales' => $playerFinancialReport->commandersSales,
			'resourcesPurchases' => $playerFinancialReport->resourcesPurchases,
			'shipsPurchases' => $playerFinancialReport->shipsPurchases,
			'commandersPurchases' => $playerFinancialReport->commandersPurchases,
		]);

		($this->commercialRouteConstructionReportHandler)($playerFinancialReport, $lastFinancialReport);

		$this->logger->debug('Processed commercial routes constructions for player {playerName}', [
			'playerId' => $player->id,
			'playerName' => $player->name,
			'commercialRoutesConstructions' => $playerFinancialReport->commercialRoutesConstructions,
		]);

		($this->creditTransactionReportHandler)($playerFinancialReport, $lastFinancialReport);

		$this->logger->debug('Processed credits transactions for player {playerName}', [
			'playerId' => $player->id,
			'playerName' => $player->name,
			'sentPlayersCreditTransactions' => $playerFinancialReport->sentPlayersCreditTransactions,
			'sentFactionsCreditTransactions' => $playerFinancialReport->sentFactionsCreditTransactions,
			'receivedPlayersCreditTransactions' => $playerFinancialReport->receivedPlayersCreditTransactions,
			'receivedFactionsCreditTransactions' => $playerFinancialReport->receivedFactionsCreditTransactions,
		]);

		($this->technologyInvestmentReportHandler)($playerFinancialReport, $lastFinancialReport);

		$this->logger->debug('Processed technology investments for player {playerName}', [
			'playerId' => $player->id,
			'playerName' => $player->name,
			'technologyInvestments' => $playerFinancialReport->technologiesInvestments,
		]);

		($this->recycledCreditsReportHandler)($playerFinancialReport, $lastFinancialReport);

		$this->logger->debug('Processed recycled credits for player {playerName}', [
			'playerId' => $player->id,
			'playerName' => $player->name,
			'recycledCredits' => $playerFinancialReport->recycledCredits,
		]);

		$this->universityInvestmentHandler->spend($playerFinancialReport, $bases);
		$this->commanderWageHandler->payWages($playerFinancialReport, $commanders, $rebelPlayer);
		$this->shipsWageHandler->payWages($playerFinancialReport, $commanders, $bases, $rebelPlayer);

		$this->logger->debug('Finished financial report for player {playerName}', [
			'playerId' => $player->id,
			'playerName' => $player->name,
		]);
	}

	private function payFactionTax(Planet $base, int $populationTax, PlayerFinancialReport $playerFinancialReport): int
	{
		if (null === ($sectorFaction = $base->system->sector->faction)) {
			return 0;
		}

		$factionTax = $this->getFactionTax($base, $populationTax);

		$sectorFaction->increaseCredit($factionTax);

		$playerFinancialReport->factionTaxes += $factionTax;

		return $factionTax;
	}

	private function getFactionTax(Planet $base, int $populationTax): int
	{
		return PercentageApplier::toInt($base->system->sector->tax, $populationTax);
	}
}
