<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Model;

interface Searchable
{
	public static function getIndex(): string;
}
