<?php

namespace App\Modules\Zeus\Application\Registry;

use App\Modules\Zeus\Domain\Exception\NoCurrentPlayerSetException;
use App\Modules\Zeus\Model\Player;

class CurrentPlayerRegistry
{
	private Player|null $currentPlayer = null;

	public function set(Player $currentPlayer): void
	{
		$this->currentPlayer = $currentPlayer;
	}

	public function has(): bool
	{
		return null !== $this->currentPlayer;
	}

	public function get(): Player
	{
		return $this->currentPlayer ?? throw new NoCurrentPlayerSetException();
	}
}
