<?php

declare(strict_types=1);

namespace Reflexive\Query;

use \Reflexive\Core\Strings;

class CreateTable extends Simple
{
	protected array $columns = [];
	protected array $primaryColumnsNames = [];
	protected array $constraints = [];

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
		$name = trim($name);

		$this->columns[$name] = [
			'name' => $name,
			'type' => trim($type),
			'isPrimary' => $isPrimary,
			'nullable' => $nullable,
			'defaultValue' => $defaultValue,
			'extra' => $extra?->value,
		];
		if($isPrimary)
			$this->primaryColumnsNames[] = $name;

		return $this;
	}
	public function setPrimary(string $columnName)
	{
		$this->queryString = null;
		$name = trim($columnName);

		$this->columns[$name]['isPrimary'] = true;
		$this->primaryColumnsNames[] = $name;
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
			$str.= Strings::quote($column['name']) .' ';
			$str.= $column['type'];
			// $str.= $column['isPrimary'] ? ' PRIMARY KEY ':'';
			$str.= $column['nullable'] === false || $column['isPrimary'] ? ' NOT NULL' : '';
			if(isset($column['defaultValue'])) {
				$str.= ' DEFAULT ';
				$defaultValue = $column['defaultValue'];
				$defaultValueType = gettype($defaultValue);
				$str.= match($defaultValueType) {
					'integer', 'double', 'float' => $defaultValue,
					'boolean' => (int)$defaultValue,
					'string' => in_array(
						$defaultValue,
						[
							'NOW()',
							'CURRENT_TIMESTAMP'
						]
					)? $defaultValue : '\''.$defaultValue.'\'',
					'object' => enum_exists($defaultValue::class)?'\''.$defaultValue->name.'\'':'NULL',
				};
			}
			$str = rtrim($str. ' ' .$column['extra'], ' '). ', ';
		}

		return rtrim($str, ', ');
	}
	protected function getPrimaryColumnsString(): string
	{
		if(empty($this->primaryColumnsNames))
			return '';

		$str = ', PRIMARY KEY (';
		$str.= rtrim('`'.implode('`, `', $this->primaryColumnsNames).'`', ', ');

		return $str.')';
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

		$this->constraints[] = [
			'name' => trim($name),
			'key' => trim($key),
			'referencedTableName' => $referencedTableName,
			'referencedKey' => $referencedKey,
			'onDelete' => $onDelete,
			'onUpdate' => $onUpdate,
		];

		return $this;
	}

	protected function getConstraintsString(): string
	{
		if(empty($this->constraints))
			return '';

		$str = '';
		foreach($this->constraints as $constraint) {
			$str.= ', CONSTRAINT ';
			$str.= Strings::quote($constraint['name']) .' FOREIGN KEY (';
			$str.= Strings::quote($constraint['key']) .') REFERENCES ';
			$str.= Strings::quote($constraint['referencedTableName']) .' (';
			$str.= Strings::quote($constraint['referencedKey']) .') ';
			$str.= 'ON DELETE '. $constraint['onDelete']->value. ' ';
			$str.= 'ON UPDATE '. $constraint['onUpdate']->value;
		}

		return $str;
	}

	public function __toString(): string
	{
		$this->bake();

		return $this->queryString ?? '';
	}
}
