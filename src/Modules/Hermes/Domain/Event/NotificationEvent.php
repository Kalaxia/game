<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Domain\Event;

use App\Modules\Hermes\Application\Builder\NotificationBuilder;

interface NotificationEvent
{
	/**
	 * @return \Generator<NotificationBuilder>
	 */
	public function getNotificationBuilders(): \Generator;
}
