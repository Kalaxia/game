<?php

declare(strict_types=1);

namespace App\Modules\Economy\Domain\Enum;

enum ActivityCategory: string
{
	case PrimaryProduction = 'primary_production';
	case Refinement = 'refinement';
	case FinalProduction = 'final_production';
	case Religion = 'religion';
	case Trade = 'trade';
	case Entertainment = 'entertainment';
}
