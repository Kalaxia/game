<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum AssetType: string
{
	case PENDING = 'PENDING';
	case IN_PROGRESS = 'IN_PROGRESS';
	case COMPLETED = 'COMPLETED';
	case FAILED = 'FAILED';
}
