<?php

namespace App\Classes\Library;

class Format
{
	/*
	 * retourne un s ou un mot au pluriel si $number est supérieur à 1
	 * arg : $number
	 *     : int => nombre qui définit ou non le pluriel
	 * arg : $return *
	 *     : str => retourne la chaine (ou s si non spécifié) si number est supérieur à 1
	 * arg :
	 */
	public static function addPlural(int $number, string $return = '', string $expression = ''): string
	{
		if ($number > 1) {
			return ('' == $expression and '' == $return)
				? 's'
				: $return;
		}

		return ('' == $expression)
			? ''
			: $expression;
	}

	public static function ordinalNumber(int $nbr): string
	{
		return match ($nbr) {
			1 => 'premier',
			2 => 'deuxième',
			3 => 'troisième',
			4 => 'quatrième',
			5 => 'cinquième',
			6 => 'sixième',
			7 => 'septième',
			default => $nbr.'ème',
		};
	}

	public static function plural(int $number, string $return = '', string $expression = ''): string
	{
		return self::addPlural($number, $return, $expression);
	}

	/*
	 * retourne un nombre formté
	 * - en mettant des espaces chaque milliers
	 * - en choisissant le nombre de chiffre après la virgule
	 * arg : $number
	 *     : int => nombre à formater
	 * arg : $decimal *
	 *     : int => nombre de chiffre après la virgule
	 */
	public static function numberFormat(int|float $number, int $decimals = 0): string
	{
		return self::number($number, $decimals);
	}

	public static function number(int|float $number, int $decimals = 0): string
	{
		if (-2 == $decimals and $number > 999999) {
			return number_format(ceil($number / 1000000), $decimals, ',', ' ').' Mio';
		} elseif ($decimals <= -1 and $number > 9999) {
			return number_format(ceil($number / 1000), $decimals, ',', ' ').' k';
		}

		return number_format($number, $decimals, ',', ' ');
	}

	public static function percent(int|float $number, int|float $base, bool $ceil = true): float
	{
		return min(100, (0 === intval(round($base)))
			? 0
			: (
				$ceil
				? ceil(($number / $base) * 100)
				: ($number / $base) * 100
			));
	}

	public static function rankingFormat($number)
	{
		if (1 == $number) {
			return '1er';
		}

		return $number.'ème';
	}

	public static function actionBuilder($action, $token, $params = [], $hasRoot = true)
	{
		$url = '';
		if ($hasRoot) {
			// @TODO replace with app_root
			$url .= '/';
		}
		$url .= 'action/';
		$url .= 'a-'.$action.'/';

		foreach ($params as $key => $value) {
			$url .= $key.'-'.$value.'/';
		}
		$url .= 'token-'.$token;

		return $url;
	}

	public static function paddingNumber($number, $size)
	{
		$digits = strlen((string) $number);

		if ($digits < $size) {
			for ($i = 0; $i < $size - $digits; ++$i) {
				$number = '0'.$number;
			}
		}

		return $number;
	}
}
