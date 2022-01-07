<?php

declare(strict_types=1);

namespace Reflexive\Query;

class Update extends Composed
{
	protected array $sets = [];

	public function __construct(array|string|null $columns = [])
	{
		parent::__construct('UPDATE', $columns);
	}

	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		$this->parameters = [];
		$this->index = 0;

		$this->queryString = $this->command. (empty($this->commandEnd) ? ' ' : '');
		$this->queryString.= $this->getIntoString();
		$this->queryString.= ' SET '.$this->getSetString();
		$this->queryString.= !empty($this->commandEnd) ? $this->commandEnd.' ' : '';
		$this->queryString.= $this->getWhereString();
		$this->queryString.= $this->getOrderString();
		$this->queryString.= $this->getLimitOffsetString();
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

		$str = '';
		foreach($this->sets as $set) {
			$setStr = '';

			if(is_array($set['value'])) { // we have an array of value, ???
				foreach($set['value'] as $value) {
					$this->parameters[$set['name'].'_'.$this->index] = $value;
					$setStr.= ':'.$set['name'].'_'.$this->index++.',';
				}
				$setStr = '('. rtrim($setStr, ',') .')';
			} else { // we have a simple value
				$this->parameters[$set['name'].'_'.$this->index] = $set['value'];
				$setStr = ':'.$set['name'].'_'.$this->index++.' ';
			}
			$str .= " \u{0060}".str_replace("\u{0060}", "\u{0060}\u{0060}", $set['name'])."\u{0060} ".'='.$setStr.', ';
		}

		return rtrim($str, ', ');
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
