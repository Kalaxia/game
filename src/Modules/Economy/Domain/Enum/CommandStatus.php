<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum CommandStatus: string
{
	case WaitingForResources = 'waiting_for_resources';
	case WaitingForQueue = 'waiting_for_queue';
	case InProgress = 'in_progress';
	case Ready = 'ready';
	case WaitingForTransport = 'waiting_for_transport';
	case Cancelled = 'cancelled';
	case Refunded = 'refunded';
	case DeliveryInProgress = 'delivery_in_progress';
	case Completed = 'delivered';
}
