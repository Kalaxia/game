<?php

namespace App\Classes\Library;

class Chronos
{
	public const SN_STR = 'STR';
	public const LN_STR = 'strate';
	public const CO_STR = 2400;
	public const SN_SEG = 'SEG';
	public const LN_SEG = 'segment';
	public const CO_SEG = 24;
	public const SN_REL = 'REL';
	public const LN_REL = 'relève';

	public const SN_MIN = '\'';
	public const LN_MIN = 'minute';
	public const SN_SEC = '\'\'';
	public const LN_SEC = 'seconde';

	/*
	 * retourne le temps restant avant la prochaine relève
	 * arg : $type
	 *     : str 'i' => minutes
	 *     : str 's' => secondes
	 */
	public static function getTimer($type)
	{
		$ret = 60 - (date($type) + 1);
		if ($ret < 10) {
			$ret = '0'.$ret;
		}

		return (int) $ret;
	}

	/*
	 * retourne le temps écoulé depuis le début du serveur
	 * arg : $type
	 *     : str 'str' => strates
	 *     : str 'seg' => segments
	 *     : str 'rel' => relèves
	 *     : str 'min' => minutes
	 *     : str 'sec' => secondes
	 */
	public static function getDate($type)
	{
		$now = time();
		$date = strtotime((string) $_ENV['SERVER_START_TIME']);
		$intr = $now - $date;
		$rel = floor($intr / 3600) + ($_ENV['SEGMENT_SHIFT'] * Chronos::CO_SEG);

		if ('str' == $type) {
			return floor($rel / Chronos::CO_STR);
		} elseif ('seg' == $type) {
			return floor($rel / Chronos::CO_SEG);
		} elseif ('rel' == $type) {
			$str = floor($rel / Chronos::CO_STR);
			$seg = floor(($rel - ($str * Chronos::CO_STR)) / Chronos::CO_SEG);

			return $rel - ($str * Chronos::CO_STR) - ($seg * Chronos::CO_SEG);
		}
	}

	private static function getRel($date)
	{
		$origin = strtotime((string) $_ENV['SERVER_START_TIME']);
		$date = strtotime((string) $date);
		$intr = $date - $origin;

		return floor($intr / 3600) + ($_ENV['SEGMENT_SHIFT'] * Chronos::CO_SEG);
	}

	/*
	 * transforme une date en temps de jeu
	 * arg : $date
	 *     : str => date au format sql (2012-08-01 18:30:00)
	 */
	public static function transform(string|\DateTimeImmutable $sourceDate, bool $returnHtml = true): string
	{
		if (\is_numeric($sourceDate)) {
			$date = new \DateTimeImmutable();
			$date->setTimestamp(intval($sourceDate));
		} elseif ($sourceDate instanceof \DateTimeImmutable) {
			$date = $sourceDate;
		} else {
			$date = new \DateTimeImmutable($sourceDate);
		}

		$releve = self::getRel($date->format('Y-m-d H:i:s'));
		$segment = floor($releve / Chronos::CO_SEG);
		$releve -= $segment * Chronos::CO_SEG;

		$return = 'SEG'.$segment.' REL'.$releve;
		$title = $date->format('j.m.Y à H:i:s');

		return $returnHtml ? '<span class="hb lt" title="'.$title.'">'.$return.'</span>' : $return;
	}

	public static function secondToFormat($seconds, $format = 'large')
	{
		$return = '';
		$rel = floor($seconds / 3600);
		$min = floor(($seconds - ($rel * 3600)) / 60);
		$sec = $seconds - ($rel * 3600) - ($min * 60);

		if ('large' == $format) {
			$return .= ($rel > 0) ? $rel.' '.Chronos::LN_REL.Format::addPlural($rel).', ' : '';
			$return .= ($min > 0) ? $min.' '.Chronos::LN_MIN.Format::addPlural($min).', ' : '';
			$return .= ($sec > 0) ? $sec.' '.Chronos::SN_SEC : '';
		} elseif ('short' == $format) {
			$return .= $rel.' '.Chronos::SN_REL.Format::addPlural($rel).', '.$min.' '.Chronos::SN_MIN.', '.$sec.' '.Chronos::SN_SEC;
		} elseif ('lite' == $format) {
			$min = ($min > 9) ? $min : '0'.$min;
			$sec = ($sec > 9) ? $sec : '0'.$sec;
			$return .= $rel.':'.$min.':'.$sec;
		}

		return trim($return, ', ');
	}
}
