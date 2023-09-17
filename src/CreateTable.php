<?php

declare(strict_types=1);

namespace Reflexive\Query;

class CreateTable extends Simple
{
	protected array $columns = [];

	public function __construct(
		protected string $name,
	)
	{
		parent::__construct();
	}

	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		$this->queryString = 'CREATE TABLE '.parent::quote($this->name).' (';
		$this->queryString.= $this->getColumnsString();
		$this->queryString.= ', '.$this->getColumnsConstrainsString();
		$this->queryString.= ') ENGINE=INNODB DEFAULT CHARSET=utf8mb4;';
	}

	// set
	public function set(Column $column): static
	{
		$this->queryString = null;

		$this->columns[$column->getName()] = $column;

		return $this;
	}

	public function getColumns(): array
	{
		return $this->columns;
	}

	protected function getColumnsString(): string
	{
		if(empty($this->columns))
			return '';

		$str = rtrim(implode(', ', $this->columns), ', ');

		// if($primaryColumnName = $this->getUIdColumnName()) {
		// 	$str.= 'PRIMARY KEY (';
		//
		// 	foreach($primaryColumnName as $columnName) {
		// 		$str.= '`'.$columnName.'`, ';
		// 	}
		// 	$str = rtrim($str, ', ').'), ';
		// }

		return $str;
	}

	protected function getColumnsConstrainsString(): string
	{
		if(empty($this->columns))
			return '';

		$str = '';
		foreach($this->columns as $column) {
			$str.= $column->getConstraint()?->asString($this->name.'_'.$column->getName(), $column->getName());
		}

		return rtrim($str, ', ');
	}

	public function __toString(): string
	{
		$this->bake();

		return $this->queryString ?? '';
	}
}
