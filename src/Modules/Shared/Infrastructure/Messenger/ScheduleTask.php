<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Messenger;

use App\Classes\Library\DateTimeConverter;
use App\Shared\Domain\Message\UniqueMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DeduplicateStamp;

readonly class ScheduleTask
{
	public function __construct(
		private MessageBusInterface $messageBus,
		private LoggerInterface $logger,
	) {
	}

	public function __invoke(object $message, ?\DateTimeImmutable $datetime): void
	{
		$this->logger->debug('Scheduling {messageClass} with parameters {parameters} to be executed at {datetime}', [
			'messageClass' => basename(get_class($message)),
			'parameters' => get_object_vars($message),
			'datetime' => $datetime,
		]);

		$stamps = [];

		if (null !== $datetime) {
			$stamps[] = DateTimeConverter::to_delay_stamp($datetime);
		}

		if ($message instanceof UniqueMessage) {
			$stamps[] = new DeduplicateStamp($message->getUniqueId());
		}

		$this->messageBus->dispatch($message, $stamps);
	}
}
