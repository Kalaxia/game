<?php

namespace App\Classes\Container;

class ArrayList implements \Stringable
{
	/** @var array * */
	protected $elements = [];

	/**
	 * @return int
	 */
	public function size()
	{
		return count($this->elements);
	}

	/**
	 * @param string $key
	 */
	public function get($key)
	{
		return $this->elements[$key] ?? null;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function exist($key)
	{
		return isset($this->elements[$key]);
	}

	public function has(string $key): bool
	{
		return isset($this->elements[$key]);
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function equal($key, $value)
	{
		return $this->exist($key) && $this->get($key) == $value;
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
	 *
	 * @return bool
	 */
	public function remove($key)
	{
		if (isset($this->elements[$key])) {
			unset($this->elements[$key]);
		}

		return false;
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
