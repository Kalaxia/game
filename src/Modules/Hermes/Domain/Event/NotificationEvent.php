<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Domain\Event;

use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Shared\Domain\Specification\SelectorSpecification;

interface NotificationEvent
{
	public function getNotificationBuilder(): NotificationBuilder;

	public function getNotificationRecipients(): array;

	public function getNotificationRecipientsSpecification(): SelectorSpecification|null;
}
