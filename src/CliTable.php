<?php
declare(strict_types = 1);
namespace SatHub\CliTable;

class CliTable
{
	/**
	 * Table data.
	 *
	 * @var array|object
	 **/
	protected $injectedData = null;

	/**
	 * Table item name.
	 **/
	protected string $itemName = 'Row';

	/**
	 * Table fields.
	 **/
	protected array $fields = [];

	/**
	 * Show column headers?
	 **/
	protected bool $showHeaders = true;

	/**
	 * Use colors?
	 **/
	protected bool $useColors = true;

	/**
	 * Center content?
	 **/
	protected bool $centerContent = true;

	/**
	 * Table Border Color.
	 **/
	protected string $tableColor = 'reset';

	protected string $headerColor = 'reset';

	/**
	 * Colors, will be populated after instantiation.
	 **/
	protected array $colors = [];

	/**
	 * Border Characters.
	 **/
	protected array $chars = [
		'top'          => '═',
		'top-mid'      => '╤',
		'top-left'     => '╔',
		'top-right'    => '╗',
		'bottom'       => '═',
		'bottom-mid'   => '╧',
		'bottom-left'  => '╚',
		'bottom-right' => '╝',
		'left'         => '║',
		'left-mid'     => '╟',
		'mid'          => '─',
		'mid-mid'      => '┼',
		'right'        => '║',
		'right-mid'    => '╢',
		'middle'       => '│ ',
	];

	public function __construct(string $itemName = 'Row', bool $useColors = true, bool $centerContent = false) {
		$this->setItemName($itemName);
		$this->setUseColors($useColors);
		$this->setCenterContent($centerContent);
		$this->defineColors();
	}

	public function getUseColors(): bool {
		return $this->useColors;
	}

	public function setUseColors(bool $bool): void {
		$this->useColors = $bool;
	}

	public function getCenterContent(): bool {
		return $this->centerContent;
	}

	public function setCenterContent(bool $bool): void {
		$this->centerContent = $bool;
	}

	public function getTableColor(): string {
		return $this->tableColor;
	}

	public function setTableColor(string $color): void {
		$this->tableColor = $color;
	}

	public function getHeaderColor(): string {
		return $this->headerColor;
	}

	public function setHeaderColor(string $color): void {
		$this->headerColor = $color;
	}

	public function getItemName(): string {
		return $this->itemName;
	}

	public function setItemName(string $name): void {
		$this->itemName = $name;
	}

	public function getShowHeaders(): bool {
		return $this->showHeaders;
	}

	public function setShowHeaders(bool $bool): void {
		$this->showHeaders = $bool;
	}

	public function setChars(array $chars): void {
		$this->chars = $chars;
	}

	public function injectData(array $data): void {
		$this->injectedData = $data;
	}

	protected function getPluralItemName(): string {
		if (count($this->injectedData) == 1) {
			return $this->getItemName();
		}

		$lastChar = strtolower(substr($this->getItemName(), strlen($this->getItemName()) - 1, 1));
		if ($lastChar == 's') {
			return $this->getItemName() . 'es';
		}
		if ($lastChar == 'y') {
			return substr($this->getItemName(), 0, strlen($this->getItemName()) - 1) . 'ies';
		}
		return $this->getItemName() . 's';
	}

	/**
	 * @param bool|object $manipulator
	 */
	public function addField(string $fieldName, string $fieldKey, $manipulator = false, string $color = 'reset'): void {
		$this->fields[$fieldKey] = [
			'name'        => $fieldName,
			'key'         => $fieldKey,
			'manipulator' => $manipulator,
			'color'       => $color,
		];
	}

	public function get(): string {
		$rowCount = 0;
		$columnLengths = [];
		$headerData = [];
		$cellData = [];

		// Headers
		if ($this->getShowHeaders()) {
			foreach ($this->fields as $field) {
				$headerData[$field['key']] = trim($field['name']);

				// Column Lengths
				if (!isset($columnLengths[$field['key']])) {
					$columnLengths[$field['key']] = 0;
				}
				$columnLengths[$field['key']] = max($columnLengths[$field['key']], strlen(trim($field['name'])));
			}
		}

		// Data
		if ($this->injectedData !== null) {
			if (count($this->injectedData)) {
				foreach ($this->injectedData as $row) {
					// Row
					$cellData[$rowCount] = [];
					foreach ($this->fields as $field) {
						$key = $field['key'];
						$value = $row[$key];
						if ($field['manipulator'] instanceof CliTableManipulator) {
							$value = trim($field['manipulator']->manipulate($value, $row, $field['name']));
						}

						$cellData[$rowCount][$key] = $value;

						// Column Lengths
						if (!isset($columnLengths[$key])) {
							$columnLengths[$key] = 0;
						}
						$c = chr(27);
						$lines = explode("\n", preg_replace("/({$c}\[(.*?)m)/s", '', $value));
						foreach ($lines as $line) {
							$columnLengths[$key] = max($columnLengths[$key], mb_strlen($line));
						}
					}
					$rowCount++;
				}
			} else {
				return 'There are no ' . $this->getPluralItemName() . PHP_EOL;
			}
		} else {
			return 'There is no injected data for the table!' . PHP_EOL;
		}

		$response = '';

		$screenWidth = trim(exec("tput cols"));

		// Idea here is we're column the accumulated length of the data
		// Then adding the quantity of column lengths to accommodate for the extra characters
		//     for when vertical pipes are placed between each column
		$dataWidth = mb_strlen($this->getTableTop($columnLengths)) + count($columnLengths);

		$spacing = '';

		// Only try and center when content is less than available space
		if ($this->getCenterContent() && (($dataWidth / 2) < $screenWidth)) {
			$spacing = str_repeat(' ', ($screenWidth - ($dataWidth / 2)) / 2);
		}

		// Now draw the table!
		$response .= $spacing . $this->getTableTop($columnLengths);
		if ($this->getShowHeaders()) {
			$response .= $spacing . $this->getFormattedRow($headerData, $columnLengths, true);
			$response .= $spacing . $this->getTableSeperator($columnLengths);
		}

		foreach ($cellData as $row) {
			$response .= $spacing . $this->getFormattedRow($row, $columnLengths);
		}

		$response .= $spacing . $this->getTableBottom($columnLengths);

		return $response;
	}

	protected function getFormattedRow(array $rowData, array $columnLengths, bool $header = false): string {
		$response = '';

		$splitLines = [];
		$maxLines = 1;
		foreach ($rowData as $key => $line) {
			$splitLines[$key] = explode("\n", $line);
			$maxLines = max($maxLines, count($splitLines[$key]));
		}

		for ($i = 0; $i < $maxLines; $i++) {
			$response .= $this->getChar('left');

			foreach ($splitLines as $key => $lines) {
				if ($header) {
					$color = $this->getHeaderColor();
				} else {
					$color = $this->fields[$key]['color'];
				}

				$line = $lines[$i] ?? '';

				$c = chr(27);
				$lineLength = mb_strwidth(preg_replace("/({$c}\[(.*?)m)/", '', $line)) + 1;
				$line = ' ' . ($this->getUseColors() ? $this->getColorFromName($color) : '') . $line;
				$response .= $line;

				for ($x = $lineLength; $x < ($columnLengths[$key] + 2); $x++) {
					$response .= ' ';
				}
				$response .= $this->getChar('middle');
			}
			$response = substr($response, 0, strlen($response) - 3) . $this->getChar('right') . PHP_EOL;
		}

		return $response;
	}

	protected function getTableTop(array $columnLengths): string {
		$response = $this->getChar('top-left');
		foreach ($columnLengths as $length) {
			$response .= $this->getChar('top', $length + 2);
			$response .= $this->getChar('top-mid');
		}
		return substr($response, 0, strlen($response) - 3) . $this->getChar('top-right') . PHP_EOL;
	}

	protected function getTableBottom(array $columnLengths): string {
		$response = $this->getChar('bottom-left');
		foreach ($columnLengths as $length) {
			$response .= $this->getChar('bottom', $length + 2);
			$response .= $this->getChar('bottom-mid');
		}
		return substr($response, 0, strlen($response) - 3) . $this->getChar('bottom-right') . PHP_EOL;
	}

	protected function getTableSeperator(array $columnLengths): string {
		$response = $this->getChar('left-mid');
		foreach ($columnLengths as $length) {
			$response .= $this->getChar('mid', $length + 2);
			$response .= $this->getChar('mid-mid');
		}
		return substr($response, 0, strlen($response) - 3) . $this->getChar('right-mid') . PHP_EOL;
	}

	protected function getChar(string $type, int $length = 1): string {
		$response = '';
		if (isset($this->chars[$type])) {
			if ($this->getUseColors()) {
				$response .= $this->getColorFromName($this->getTableColor());
			}
			$char      = trim($this->chars[$type]);
			$response .= str_repeat($char, $length);
		}
		return $response;
	}

	protected function defineColors(): void {
		$this->colors = [
			'blue'    => chr(27) . '[1;34m',
			'red'     => chr(27) . '[1;31m',
			'green'   => chr(27) . '[1;32m',
			'yellow'  => chr(27) . '[1;33m',
			'black'   => chr(27) . '[1;30m',
			'magenta' => chr(27) . '[1;35m',
			'cyan'    => chr(27) . '[1;36m',
			'white'   => chr(27) . '[1;37m',
			'grey'    => chr(27) . '[0;37m',
			'reset'   => chr(27) . '[0m',
		];
	}

	protected function getColorFromName(string $colorName): string {
		if (isset($this->colors[$colorName])) {
			return $this->colors[$colorName];
		}
		return $this->colors['reset'];
	}

	public function display(): void {
		print $this->get();
	}
}
