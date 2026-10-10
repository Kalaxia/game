<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model\Battle;

/**
 * A rule broken by a fleet formation. The code and parameters allow the UI to translate it.
 */
final readonly class FormationViolation implements \Stringable
{
	public const string EMPTY = 'empty';
	public const string OUTSIDE_GRID = 'outside_grid';
	public const string CELL_TAKEN = 'cell_taken';
	public const string SQUADRON_SIZE = 'squadron_size';

	/**
	 * @param array<string, int|string> $parameters
	 */
	public function __construct(
		public string $code,
		public array $parameters = [],
	) {
	}

	public function __toString(): string
	{
		return match ($this->code) {
			self::EMPTY => 'The formation has no squadron',
			self::OUTSIDE_GRID => sprintf('Cell %s is outside the formation grid', $this->parameters['cell']),
			self::CELL_TAKEN => sprintf('Cell %s holds more than one squadron', $this->parameters['cell']),
			self::SQUADRON_SIZE => sprintf(
				'A %s squadron holds 1 to %d ships, %d given on cell %s',
				$this->parameters['type'],
				$this->parameters['max'],
				$this->parameters['ships'],
				$this->parameters['cell'],
			),
			default => $this->code,
		};
	}
}
