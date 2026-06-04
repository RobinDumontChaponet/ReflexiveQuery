<?php

declare(strict_types=1);

namespace Reflexive\Query;

use \Reflexive\Core\Strings;

class CreateTable extends TableQuery
{
	protected array $columns = [];
	protected array $primaryColumnsNames = [];
	protected array $constraints = [];

	#[\Override]
	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		$this->queryString = 'CREATE TABLE '.Strings::quote($this->name).' (';
		$this->queryString.= $this->getColumnsString();
		$this->queryString.= $this->getPrimaryColumnsString();
		$this->queryString.= $this->getConstraintsString();
		$this->queryString.= ') ENGINE=INNODB DEFAULT CHARSET=utf8mb4;';
	}

	// addColumn
	public function addColumn(string $name, string $type, bool $isPrimary = false, bool|null $nullable = null, mixed $defaultValue = null, ?ColumnExtra $extra = null): static
	{
		$this->queryString = null;

		$column = $this->addColumnDefinition($name, $type, $isPrimary, $nullable, $defaultValue, $extra);
		$this->columns[$column['name']] = $column;

		if($isPrimary)
			$this->addPrimaryColumnName($column['name']);

		return $this;
	}
	public function setPrimary(string $columnName): static
	{
		$this->queryString = null;
		$name = trim($columnName);

		if(!isset($this->columns[$name]))
			throw new \TypeError('Unknown column.');

		$this->columns[$name]['isPrimary'] = true;
		$this->addPrimaryColumnName($name);

		return $this;
	}

// 	// set
// 	public function set(Column $column): static
// 	{
// 		$this->queryString = null;
//
// 		$this->columns[$column->getName()] = $column;
//
// 		return $this;
// 	}

	public function getColumns(): array
	{
		return $this->columns;
	}

	protected function getColumnsString(): string
	{
		if(empty($this->columns))
			throw new \TypeError('No columns.');

		// $str = rtrim(implode(', ', $this->columns), ', ');

		$str = '';
		foreach($this->columns as $column) {
			$str.= $this->getColumnString($column). ', ';
		}

		return rtrim($str, ', ');
	}
	protected function getPrimaryColumnsString(): string
	{
		if(empty($this->primaryColumnsNames))
			return '';

		return ', PRIMARY KEY ('.$this->getColumnListString($this->primaryColumnsNames).')';
	}

	// protected function getColumnsConstraintsString(): string
// 	{
// 		if(empty($this->columns))
// 			return '';
//
// 		$str = '';
// 		foreach($this->columns as $column) {
// 			$str.= $column->getConstraint()?->asString($this->name.'_'.$column->getName(), $column->getName());
// 		}
//
// 		return rtrim($str, ', ');
// 	}

	// constraints
	public function addConstraint(string $name, string $key, string $referencedTableName, string $referencedKey, ConstraintAction $onDelete = ConstraintAction::noAction, ConstraintAction $onUpdate = ConstraintAction::noAction): static
	{
		$this->queryString = null;

		$this->constraints[] = $this->addConstraintDefinition($name, $key, $referencedTableName, $referencedKey, $onDelete, $onUpdate);

		return $this;
	}

	protected function getConstraintsString(): string
	{
		if(empty($this->constraints))
			return '';

		$str = '';
		foreach($this->constraints as $constraint) {
			$str.= ', '.$this->getConstraintString($constraint);
		}

		return $str;
	}

	protected function addPrimaryColumnName(string $columnName): void
	{
		if(!in_array($columnName, $this->primaryColumnsNames, true))
			$this->primaryColumnsNames[] = $columnName;
	}

}
