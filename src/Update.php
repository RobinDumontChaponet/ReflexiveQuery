<?php

declare(strict_types=1);

namespace Reflexive\Query;

class Update extends Push
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
			$str.= $this->quote($set['name']).'='.$setStr.', ';
		}

		return rtrim($str, ', ');
	}
}
