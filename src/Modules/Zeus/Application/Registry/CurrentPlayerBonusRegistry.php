<?php

namespace App\Modules\Zeus\Application\Registry;

use App\Modules\Zeus\Model\PlayerBonus;
use Symfony\Contracts\Service\ResetInterface;

class CurrentPlayerBonusRegistry implements ResetInterface
{
	private ?PlayerBonus $playerBonus = null;
	private bool $isInitialized = false;

	public function setPlayerBonus(PlayerBonus $playerBonus): void
	{
		$this->playerBonus = $playerBonus;
		$this->isInitialized = true;
	}

	public function getPlayerBonus(): ?PlayerBonus
	{
		return $this->playerBonus;
	}

	public function isInitialized(): bool
	{
		return $this->isInitialized;
	}

	public function reset(): void
	{
		$this->playerBonus = null;
		$this->isInitialized = false;
	}
}
