<?php

declare(strict_types=1);

namespace Reflexive\Query;

abstract class Push extends Composed
{
	protected array $sets = [];

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

	protected function getIntoString(): string
	{
		if(empty($this->tables))
			return '';

		$str = '';
		foreach($this->tables as $key => $table) {
			$str.= $this->quote($table);

			if(is_string($key))
				$str.= ' '.$key;

			$str.= ', ';
		}

		return rtrim($str, ', ');
	}
}
