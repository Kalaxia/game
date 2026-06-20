<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model;

use App\Modules\Ares\Model\Squadron;
use App\Modules\Demeter\Model\Color;

class VirtualCommander implements CommanderInterface
{
	public function __construct(
		public ?int $id,
		public string $name,
		public int $level,
		public Color $faction,
		/**
		 * @var list<Squadron>
		 */
		public array $army = [],
	) {

	}
}
