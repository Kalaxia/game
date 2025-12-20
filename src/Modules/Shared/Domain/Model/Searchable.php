<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Model;

interface Searchable
{
	public static function getIndex(): string;

	/**
	 * @return array<string, mixed>
	 */
	public function getSearchableData(): array;

	/**
	 * @return array{
	 *     filterableAttributes?: list<string>,
	 *     sortableAttributes?: list<string>,
	 * }
	 */
	public static function getIndexSettings(): array;
}
