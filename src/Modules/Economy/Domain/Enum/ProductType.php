<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum ProductType: string
{
	case Ship = 'ship';
	case Component = 'component';
	case Resource = 'resource';
}
