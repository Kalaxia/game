<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Molecules;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Domain\Service\Base\Building\BuildingDataHandler;
use App\Modules\Athena\Domain\Service\Base\Building\GetTimeCost;
use App\Modules\Athena\Infrastructure\Validator\CanMakeBuilding;
use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Galaxy\Domain\Entity\Planet;
use App\Modules\Galaxy\Helper\PlanetHelper;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Shared\Infrastructure\Twig\Components\Molecules\Card;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'BuildingCard',
	template: 'components/Molecules/Base/BuildingCard.html.twig',
)]
final class BuildingCard extends Card
{
	public int $buildingIdentifier;
	public Planet $base;
	public Technology $technology;
	/** @var list<BuildingQueue> */
	public array $buildingQueues;
	public int $buildingQueuesCount;
	public int $level;
	public int $realLevel;
	public ConstraintViolationListInterface $requirements;

	public function __construct(
		private readonly BuildingDataHandler $buildingDataHandler,
		private readonly GetTimeCost $getTimeCost,
		private readonly BuildingLevelHandler $buildingLevelHandler,
		private readonly PlanetHelper $planetHelper,
		private readonly ValidatorInterface $validator,
	) {
	}

	public function mount(Planet $planet, Technology $technology, int $buildingIdentifier, array $buildingQueues, int $buildingQueuesCount): void
	{
		$this->base = $planet;
		$this->technology = $technology;
		$this->buildingIdentifier = $buildingIdentifier;
		$this->buildingQueues = $buildingQueues;
		$this->buildingQueuesCount = $buildingQueuesCount;
		$this->level = $this->buildingLevelHandler->getBuildingLevel($planet, $buildingIdentifier);
		$this->realLevel = $this->buildingLevelHandler->getBuildingRealLevel(
			$planet,
			$buildingIdentifier,
			$buildingQueues,
		);

		$buildingConstructionOrder = new BuildingConstructionOrder(
			planet: $planet,
			targetLevel: $this->getNextLevel(),
			buildingIdentifier: $buildingIdentifier,
			technology: $technology,
		);

		$this->requirements = $this->validator->validate($buildingConstructionOrder, new CanMakeBuilding($buildingQueuesCount));
	}

	#[\Override]
	public function isDisabled(): bool
	{
		// TODO investigate this condition. Previously `not realLevel` \_()_/ in `ViewGenerator` Controller
		return false;
	}

	public function getLevel(): int
	{
		return $this->level;
	}

	public function getNextLevel(): int
	{
		return $this->realLevel + 1;
	}

	public function getMaxLevel(): int
	{
		return $this->planetHelper->getBuildingInfo($this->buildingIdentifier, 'maxLevel', $this->base->typeOfBase);
	}

	public function getName(): string
	{
		return $this->planetHelper->getBuildingInfo($this->buildingIdentifier, 'frenchName');
	}

	public function getResourceCost(): ?int
	{
		return $this->buildingDataHandler->getBuildingResourceCost($this->buildingIdentifier, $this->realLevel + 1);
	}

	public function getTimeCost(): ?int
	{
		return ($this->getTimeCost)($this->buildingIdentifier, $this->realLevel + 1);
	}
}
