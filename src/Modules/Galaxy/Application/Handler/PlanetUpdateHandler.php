<?php

declare(strict_types=1);

namespace App\Modules\Galaxy\Application\Handler;

use App\Classes\Library\Game;
use App\Modules\Galaxy\Application\Message\PlanetUpdateMessage;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Domain\Repository\PlanetRepositoryInterface;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Galaxy\Manager\PlanetManager;
use App\Modules\Galaxy\Resource\PlanetResource;
use App\Modules\Shared\Application\Service\CountMissingSystemUpdates;
use App\Modules\Shared\Domain\Service\GameTimeConverter;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class PlanetUpdateHandler
{
	private const int MAX_UPDATES = 10;

	public function __construct(
		private ClockInterface $clock,
		private GameTimeConverter $gameTimeConverter,
		private BonusApplierInterface $bonusApplier,
		private EntityManagerInterface $entityManager,
		private PlayerBonusManager $playerBonusManager,
		private CountMissingSystemUpdates $countMissingSystemUpdates,
		private PlanetManager $planetManager,
		private PlanetRepositoryInterface $planetRepository,
		private PlanetHelper $planetHelper,
		private MessageBusInterface $messageBus,
		private LoggerInterface $logger,
	) {
	}

	public function __invoke(PlanetUpdateMessage $message): void
	{
		$planet = $this->planetRepository->get($message->planetId)
			?? throw new \RuntimeException(sprintf('Base %s not found', $message->planetId));

		$missingUpdatesCount = ($this->countMissingSystemUpdates)($planet);
		if (0 === $missingUpdatesCount) {
			return;
		}

		if (null === $planet->player) {
			$this->updateNpcPlanet($planet, $missingUpdatesCount);
		} else {
			$this->updatePlayerPlanet($planet, $missingUpdatesCount);
		}
	}

	private function updateNpcPlanet(Planet $planet, int $missingUpdatesCount): void
	{
		// update time
		$planet->updatedAt = $this->clock->now();
		$planet->resourcesStorage = min(
			$planet->resourcesStorage + $planet->getProducedResources() * $missingUpdatesCount,
			$planet->getMaxResources(),
		);
		$planet->danger = min(
			$planet->danger + Planet::REPOPDANGER * $missingUpdatesCount,
			$planet->maxDanger,
		);

		$this->planetRepository->save($planet);
	}

	private function updatePlayerPlanet(Planet $planet, int $missingUpdatesCount): void
	{
		$playerBonus = $this->playerBonusManager->getBonusByPlayer($planet->player);

		$secondsPerGameCycle = $this->gameTimeConverter->convertGameCyclesToSeconds(1);

		try {
			$this->entityManager->beginTransaction();

			$launchNewMessage = false;

			for ($i = 0; $i < $missingUpdatesCount; ++$i) {
				if (self::MAX_UPDATES === $i) {
					$launchNewMessage = true;

					break;
				}
				$this->updateResources($planet, $playerBonus);
				$this->updateAntiSpy($planet);

				$planet->updatedAt = $planet->updatedAt->modify(sprintf('+%d seconds', $secondsPerGameCycle));
			}

			$this->planetRepository->save($planet);

			$this->entityManager->commit();

			if (true === $launchNewMessage) {
				$this->messageBus->dispatch(new PlanetUpdateMessage($planet->id));

				$this->logger->debug('Dispatched new planet update message for the next iterations for planet {baseName} of player {playerName}', [
					'baseName' => $planet->name,
					'planetId' => $planet->id,
					'playerName' => $planet->player->name,
					'playerId' => $planet->player->id,
				]);
			}
		} catch (\Throwable $e) {
			$this->entityManager->rollback();

			throw $e;
		}
	}

	protected function updateResources(Planet $planet, PlayerBonus $playerBonus): void
	{
		$addResources = Game::resourceProduction(
			$this->planetHelper->getBuildingInfo(
				PlanetResource::REFINERY,
				'level',
				$planet->levelRefinery,
				'refiningCoefficient'
			),
			$planet->coefResources,
		);
		$addResources += $this->bonusApplier->apply($addResources, PlayerBonusId::REFINERY_REFINING, $playerBonus);

		$this->planetManager->increaseResources($planet, intval(round($addResources)), false);
	}

	protected function updateAntiSpy(Planet $planet): void
	{
		$planet->antiSpyAverage = intval(round((($planet->antiSpyAverage * (24 - 1)) + $planet->iAntiSpy) / 24));
	}
}
