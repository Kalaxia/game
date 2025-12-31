<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Helper;

class CheckName
{
	public int $minLength = 2;
	public int $maxLength = 15;

	public function getMinLength(): int
	{
		return $this->minLength;
	}

	public function getMaxLength(): int
	{
		return $this->maxLength;
	}

	public function setMinLength(int $i): void
	{
		$this->minLength = $i;
	}

	public function setMaxLength(int $i): void
	{
		$this->maxLength = $i;
	}

	public function checkLength(string $str): bool
	{
		$length = strlen($str);

		return $length >= $this->minLength && $length <= $this->maxLength;
	}

	public function checkChar($str): int|false
	{
		return preg_match('^[\p{L}\p{N}]*\p{L}[\p{L}\p{N}]*$^', (string) $str);
	}

	public function checkBeauty($str)
	{
		$newStr = ucfirst((string) $str);

		$revStr = strrev($newStr);
		$troncateStr = $newStr;
		$number = '';
		for ($i = 0; $i < strlen($revStr); ++$i) {
			if (is_numeric($revStr[$i])) {
				$number .= $revStr[$i];
				$troncateStr = substr($troncateStr, 0, -1);
			} else {
				$number = strrev($number);
				$number = intval($number);
				break;
			}
		}

		if (0 !== $number) {
			$brn = ['M', 'CM', 'D', 'CD', 'C', 'XC', 'L', 'XL', 'X', 'IX', 'V', 'IV', 'I'];
			$ban = [1000, 900, 500, 400, 100, 90, 50, 40, 10, 9, 5, 4, 1];

			if ($number > 1200) {
				$number = random_int(50, 1200);
			}
			if ($number <= 0) {
				$number = 1;
			}
			$number = intval($number);
			$romanNumber = '';
			$i = 0;

			if (1 == $number) {
				$romanNumber = 'Ier';
				$number = 0;
			}

			while ($number > 0) {
				if ($number >= $ban[$i]) {
					$number -= $ban[$i];
					$romanNumber .= $brn[$i];
				} else {
					++$i;
				}
			}

			$newStr = trim($troncateStr).' '.$romanNumber;

			if (strlen($newStr) > $this->maxLength) {
				$newStr = substr($newStr, 0, $this->maxLength);
			}
		}

		if ($newStr === $str) {
			return true;
		} else {
			return $newStr;
		}
	}

	public static function getPackOfNames($size = 5, $used = false, $tag = [])
	{
	}

	public static function randomize(): string
	{
		$name = ['Ametah', 'Anla', 'Aumshi', 'Bastier', 'Enigma', 'Eirukis', 'Erah', 'Ehdis', 'Fransa', 'Greider', 'Grerid', 'Haema', 'Hemhild', 'Renga', 'Hidar', 'Horski', 'Hreirek', 'Hroa', 'Hordis', 'Hydring', 'Imsin', 'Asmin', 'Ansami', 'Kar', 'Kili', 'Kolver', 'Kolfinna', 'Lisa', 'Marta', 'Meto', 'Leto', 'Ragni', 'Ranela', 'Runa', 'Siri', 'Mastro', 'Svenh', 'Thalestris', 'Thannd', 'Arsine', 'Val', 'Vori', 'Yi', 'Agata', 'Agneta', 'Nolgi', 'Edla', 'Else', 'Eyja', 'Jensine', 'Kirsten', 'Maeva', 'Malena', 'Magarte', 'Olava', 'Petrine', 'Rigmor', 'Signy', 'Sigrid', 'Skjorta'];

		return $name[random_int(0, (count($name) - 1))];
	}
}
