<?php

namespace App\Classes\Container;

class StackList implements \Stringable
{
	/** @var array * */
	private $elements = [];

	/**
	 * @return int
	 */
	public function size()
	{
		return count($this->elements);
	}

	/**
	 * @param int $index
	 */
	public function get($index = 0)
	{
		return $this->elements[$index] ?? null;
	}

	public function all(): array
	{
		return $this->elements;
	}

	/**
	 * @param int $index
	 *
	 * @return bool
	 */
	public function exist($index)
	{
		return isset($this->elements[$index]);
	}

	/**
	 * @param string $key
	 */
	public function add($key, $value)
	{
		$this->elements[$key] = $value;
	}

	/**
	 * @param string $key
	 */
	public function increase($key, $value)
	{
		$this->elements[$key] =
						(isset($this->elements[$key]))
						? $this->elements[$key] + $value
						: $value
		;
	}

	/**
	 * @param string $key
	 */
	public function insert($key, $value)
	{
		if (count($this->elements) < $key) {
			$this->elements[$key] = $value;
		} else {
			// décalage des tous les index qui suivent
			$begin = array_slice($this->elements, 0, $key);
			$begin[] = $value;
			$end = array_slice($this->elements, $key);
			$this->elements = array_merge($begin, $end);
		}
	}

	public function append($value)
	{
		$this->elements[] = $value;
	}

	public function prepend($value)
	{
		array_unshift($this->elements, $value);
	}

	/**
	 * @param int $index
	 */
	public function remove($index)
	{
		if ($index < 0) {
			$index = count($this->elements) + $index;
		}
		if (!isset($this->elements[$index])) {
			return false;
		}
		$begin = array_slice($this->elements, 0, $index);
		$end = array_slice($this->elements, $index + 1);
		$this->elements = array_merge($begin, $end);
	}

	public function clear()
	{
		$this->elements = [];
	}

	public function __toString(): string
	{
		return sprintf('[%s]', implode(',', array_values($this->elements)));
	}
}
