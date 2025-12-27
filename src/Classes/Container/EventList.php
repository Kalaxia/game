<?php

namespace App\Classes\Container;

class EventList
{
	// contient des ArrayList('date', 'eventType', 'eventId')
	// format de la date : 'AAAA-MM-JJ HH:MM:SS' - SQL FORMAT
	private $events = [];

	/**
	 * @return int
	 */
	public function size()
	{
		return count($this->events);
	}

	/**
	 * @param int $index
	 *
	 * @return ArrayList
	 */
	public function get($index = 0)
	{
		return $this->events[$index] ?? null;
	}

	/**
	 * @param string $date
	 * @param string $eventType
	 * @param string $eventId
	 * @param string $eventInfo
	 */
	public function add($date, $eventType, $eventId, $eventInfo = null)
	{
		$event = new ArrayList();
		$event->add('date', $date);
		$event->add('eventType', $eventType);
		$event->add('eventId', $eventId);
		$event->add('eventInfo', $eventInfo);

		$index = 0;
		$found = false;
		foreach ($this->events as $e) {
			if ($e->get('date') > $date) {
				$found = true;
				break;
			}
			++$index;
		}
		if ($found) {
			$begin = array_slice($this->events, 0, $index);
			$begin[] = $event;
			$end = array_slice($this->events, $index);
			$this->events = array_merge($begin, $end);
		} else {
			$this->events[] = $event;
		}
	}

	/**
	 * @param int $index
	 *
	 * @return bool
	 */
	public function remove($index)
	{
		if ($index < 0) {
			$index = count($this->events) + $index;
		}
		if (!isset($this->events[$index])) {
			return false;
		}
		$begin = array_slice($this->events, 0, $index);
		$end = array_slice($this->events, $index + 1);
		$this->events = array_merge($begin, $end);
	}

	public function clear()
	{
		$this->events = [];
	}
}
