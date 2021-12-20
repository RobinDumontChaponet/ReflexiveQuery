<?php

declare(strict_types=1);

namespace Reflexive\Query;

class Insert extends Composed
{
	protected array $sets = [];

	public function __construct(?array $columns = [])
	{
		parent::__construct('INSERT INTO');
		$this->setColumns($columns);
	}

	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		$this->parameters = [];
		$this->index = 0;

		$this->queryString = $this->command. (empty($this->commandEnd) ? ' ' : '');
		$this->queryString.= $this->getIntoString();
		$this->queryString.= $this->getSetString();
		$this->queryString.= !empty($this->commandEnd) ? $this->commandEnd.' ' : '';
	}

	// set
	public function set(string $name, string|int|float|array $value = null): static
	{
		$this->queryString = null;

		$this->sets[] = [
			'name' => trim($name),
			'value' => $value,
		];

		return $this;
	}
	protected function getSetString(): string
	{
		if(empty($this->sets))
			return '';

		$str = ' (';
		foreach($this->sets as $set) { // columns names
			$str .= "\u{0060}".str_replace("\u{0060}", "\u{0060}\u{0060}", $set['name'])."\u{0060} ".', ';
		}

		$str = rtrim($str, ', ').') values (';
		foreach($this->sets as $set) { // values
			$this->parameters[$set['name'].'_'.$this->index] = $set['value'];
			$str .= ':'.$set['name'].'_'.$this->index++.', ';
		}

		return rtrim($str, ', ').') ';
	}

	protected function getIntoString(): string
	{
		if(empty($this->tables))
			return '';

		$str = '';
		foreach($this->tables as $key => $table) {
			if(!str_starts_with($table, "\u{0060}"))
				$str.= "\u{0060}".$table."\u{0060}";
			else
				$str.= $table;

			if(is_string($key))
				$str.= ' '.$key;

			$str.= ', ';
		}

		return rtrim($str, ', ');
	}
}
