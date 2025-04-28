<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Organisms\Trade;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Model\OrbitalBase;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'CommanderSalesForm',
	template: 'components/Organisms/Trade/CommanderSalesForm.html.twig',
)]
readonly class CommanderSalesForm
{
	public array $commanders;
	public float $currentRate;

	public function __construct(
		private CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		private CommanderRepositoryInterface $commanderRepository
	) {
	}

	public function mount(float $currentRate): void
	{
		$this->currentRate = $currentRate;

		$this->commanders = $this->commanderRepository->getBaseCommanders(
			$this->currentPlayerBasesRegistry->current(),
			[Commander::INSCHOOL, Commander::RESERVE],
			['experience' => 'DESC'],
		);
	}

	public function getCommanderPrice(Commander $commander)
	{
		return intval(ceil($commander->experience * $this->currentRate));
	}
}
