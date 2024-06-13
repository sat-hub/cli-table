<?php
declare(strict_types = 1);
namespace SatHub\CliTable;

class CliTableManipulator
{
	/**
	 * Stores the type of manipulation to perform.
	 **/
	protected string $type = '';

	/**
	 * @throws \InvalidArgumentException Given type is not a callable.
	 */
	public function __construct(string $type) {
		$this->type = $type;
		if (!$type || !is_callable([$this, $type])) {
			throw new \InvalidArgumentException('Invalid data manipulator type.');
		}
	}

	/**
	 * This is used by the Table class to manipulate the data passed in and returns the formatted data.
	 *
	 * @param mixed $value
	 */
	public function manipulate($value, array $row = [], string $fieldName = ''): string {
		$type = $this->type;
		return $this->$type($value, $row, $fieldName);
	}

	/**
	 * Changes 12300.23 to $12,300.23.
	 */
	protected function dollar(float $value): string {
		return '$' . number_format($value, 2);
	}

	/**
	 * Changes 1372132121 to 25-06-2013.
	 */
	protected function date(int $value): string {
		return date('d-m-Y', $value);
	}

	/**
	 * Changes 1372132121 to 25th June 2013.
	 */
	protected function datelong(int $value): string {
		return date('jS F Y', $value);
	}

	/**
	 * Changes 1372132121 to 1:48 pm.
	 */
	protected function time(int $value): string {
		return date('g:i a', $value);
	}

	/**
	 * Changes 1372132121 to 25th June 2013, 1:48 pm.
	 */
	protected function datetime(int $value): string {
		return date('jS F Y, g:i a', $value);
	}

	/**
	 * Changes 1372132121 to 25th June 2013, 1:48 pm, 1372132121 to Today, 1:48 pm, 1372132121 to Yesterday, 1:48 pm.
	 */
	protected function nicetime(int $value): string {
		if ($value > mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'))) {
			return 'Today ' . date('g:i a', $value);
		}
		if ($value > mktime(0, 0, 0, (int)date('m'), (int)date('d') - 1, (int)date('Y'))) {
			return 'Yesterday ' . date('g:i a', $value);
		}
		return date('jS F Y, g:i a', $value);
	}

	protected function duetime(int $value): string {
		$isPast = false;
		if ($value > time()) {
			$seconds = $value - time();
		} else {
			$isPast  = true;
			$seconds = time() - $value;
		}

		$text = $seconds . ' second' . ($seconds == 1 ? '' : 's');
		if ($seconds >= 60) {
			$minutes  = floor($seconds / 60);
			$seconds -= ($minutes * 60);
			$text     = $minutes . ' minute' . ($minutes == 1 ? '' : 's');
			if ($minutes >= 60) {
				$hours    = floor($minutes / 60);
				$minutes -= ($hours * 60);
				$text     = $hours . ' hours, ' . $minutes . ' minute' . ($hours == 1 ? '' : 's');
				if ($hours >= 24) {
					$days   = floor($hours / 24);
					$hours -= ($days * 24);
					$text   = $days . ' day' . ($days == 1 ? '' : 's');
					if ($days >= 365) {
						$years = floor($days / 365);
						$days -= ($years * 365);
						$text  = $years . ' year' . ($years == 1 ? '' : 's');
					}
				}
			}
		}
		return $text . ($isPast ? ' ago' : '');
	}

	protected function nicenumber(float $value): string {
		return number_format($value);
	}

	/**
	 * Changes 1372132121 to June.
	 */
	protected function month(int $value): string {
		return date('F', $value);
	}

	/**
	 * Changes 1372132121 to 2013.
	 */
	protected function year(int $value): string {
		return date('Y', $value);
	}

	/**
	 * Changes 1372132121 to June 2013.
	 */
	protected function monthyear(int $value): string {
		return date('F Y', $value);
	}

	/**
	 * Changes 50.2 to 50%.
	 *
	 * @param mixed $value
	 */
	protected function percent($value): string {
		return intval($value) . '%';
	}

	/**
	 * Changes 0/false and 1/true to No and Yes respectively.
	 *
	 * @param mixed $value
	 */
	protected function yesno($value): string {
		return $value ? 'Yes' : 'No';
	}

	/**
	 * Strips input of any HTML.
	 */
	protected function text(string $value): string {
		return strip_tags($value);
	}
}
