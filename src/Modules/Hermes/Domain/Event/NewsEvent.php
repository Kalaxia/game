<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Domain\Event;

use App\Modules\Demeter\Model\Color;
use App\Modules\Hermes\Application\Builder\NewsBuilder;

interface NewsEvent
{
	/**
	 * @param list<Color> $factions
	 *
	 * @return \Generator<NewsBuilder>
	 */
	public function getNewsBuilders(array $factions): \Generator;
}
