<?php

declare(strict_types=1);

namespace Reflexive\Query;

class Insert extends Push
{
	protected array $sets = [];

	public function __construct(array|string|null $columns = [])
	{
		parent::__construct('INSERT INTO', $columns);
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

	protected function getSetString(): string
	{
		if(empty($this->sets))
			return '';

		$str = ' (';
		foreach($this->sets as $set) { // columns names
			$str .= $this->quoteName($set['name']).', ';
		}

		$str = rtrim($str, ', ').') values (';
		foreach($this->sets as $set) { // values
			$this->parameters[$set['name'].'_'.$this->index] = $set['value'];
			$str .= ':'.$set['name'].'_'.$this->index++.', ';
		}

		return rtrim($str, ', ').') ';
	}
}
