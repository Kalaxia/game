<?php

namespace App\Shared\Infrastructure\Twig;

use App\Classes\Library\Chronos;
use App\Classes\Library\Format;
use App\Classes\Library\Parser;
use App\Modules\Shared\Domain\Service\GameTimeConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FormatterExtension extends AbstractExtension
{
	public function __construct(
		private readonly GameTimeConverter $gameTimeConverter,
		private readonly Parser $parser,
	) {
	}

	#[\Override]
	public function getFilters(): array
	{
		return [
			new TwigFilter('parse', fn (string $content) => $this->parser->parse($content)),
			new TwigFilter('number', fn (int|float|null $number, int $decimals = 0) => Format::numberFormat($number ?? 0, $decimals)),
			new TwigFilter('ranking', fn (int|float $number) => Format::rankingFormat($number)),
			new TwigFilter('ordinal_number', fn (int|float $number) => Format::ordinalNumber($number)),
			new TwigFilter('plural', fn (int|float $number) => Format::plural($number)),
			new TwigFilter('percent', fn (int|float $number, int|float $base) => Format::percent($number, $base)),
			new TwigFilter('lite_seconds', fn (int $seconds) => Chronos::secondToFormat($seconds, 'lite')),
			new TwigFilter('large_seconds', fn (int $seconds) => Chronos::secondToFormat($seconds, 'large')),
			new TwigFilter('short_seconds', fn (int $seconds) => Chronos::secondToFormat($seconds, 'short')),
			new TwigFilter('game_date', fn (string|\DateTimeImmutable $date, bool $returnHtml = true) => $this->gameTimeConverter->convertDatetimeToGameDate($date, $returnHtml)),
			new TwigFilter('unserialize', fn (string $data) => \unserialize($data)),
		];
	}

	#[\Override]
	public function getFunctions(): array
	{
		return [
			new TwigFunction('get_parser_toolbar', fn () => $this->parser->getToolbar()),
			new TwigFunction('get_game_timer', fn (string $type) => Chronos::getTimer($type)),
			new TwigFunction('get_game_date', fn (string $type) => Chronos::getDate($type)),
		];
	}
}
