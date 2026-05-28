<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Domain\Event;

use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

interface NotificationEvent
{
	/**
	 * @return \Generator<NotificationBuilder>
	 */
	public function getNotificationBuilders(
		UrlGeneratorInterface $urlGenerator,
		TranslatorInterface $translator,
	): \Generator;
}
