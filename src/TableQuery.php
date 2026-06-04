<?php

declare(strict_types=1);

namespace Reflexive\Query;

use \Reflexive\Core\Strings;

abstract class TableQuery extends Simple
{
	public function __construct(
		protected string $name,
	) {
		$this->name = trim($this->name);
		parent::__construct();
	}

	abstract protected function bake(): void;

	#[\Override]
	public function prepare(\PDO $pdo): \PDOStatement
	{
		$this->bake();

		return parent::prepare($pdo);
	}

	protected function addColumnDefinition(string $name, string $type, bool $isPrimary = false, bool|null $nullable = null, mixed $defaultValue = null, ?ColumnExtra $extra = null): array
	{
		$name = trim($name);

		return [
			'name' => $name,
			'type' => trim($type),
			'isPrimary' => $isPrimary,
			'nullable' => $nullable,
			'defaultValue' => $defaultValue,
			'extra' => $extra?->value,
		];
	}

	protected function getColumnString(array $column): string
	{
		$str = Strings::quote($column['name']) .' ';
		$str.= $column['type'];
		$str.= $column['nullable'] === false || $column['isPrimary'] ? ' NOT NULL' : '';

		if(isset($column['defaultValue'])) {
			$str.= ' DEFAULT ';
			$str.= $this->getDefaultValueString($column['defaultValue']);
		}

		return rtrim($str. ' ' .($column['extra'] ?? ''), ' ');
	}

	protected function getColumnListString(array|string $columns): string
	{
		if(is_string($columns))
			$columns = [$columns];

		if(empty($columns))
			throw new \TypeError('No columns.');

		return implode(', ', array_map(
			static fn(string $column): string => Strings::quote(trim($column)),
			$columns
		));
	}

	protected function getConstraintString(array $constraint): string
	{
		$str = 'CONSTRAINT ';
		$str.= Strings::quote($constraint['name']) .' FOREIGN KEY (';
		$str.= Strings::quote($constraint['key']) .') REFERENCES ';
		$str.= Strings::quote($constraint['referencedTableName']) .' (';
		$str.= Strings::quote($constraint['referencedKey']) .') ';
		$str.= 'ON DELETE '. $constraint['onDelete']->value. ' ';
		$str.= 'ON UPDATE '. $constraint['onUpdate']->value;

		return $str;
	}

	protected function addConstraintDefinition(string $name, string $key, string $referencedTableName, string $referencedKey, ConstraintAction $onDelete = ConstraintAction::noAction, ConstraintAction $onUpdate = ConstraintAction::noAction): array
	{
		return [
			'name' => trim($name),
			'key' => trim($key),
			'referencedTableName' => $referencedTableName,
			'referencedKey' => $referencedKey,
			'onDelete' => $onDelete,
			'onUpdate' => $onUpdate,
		];
	}

	private function getDefaultValueString(mixed $defaultValue): string|int|float
	{
		$defaultValueType = gettype($defaultValue);

		return match($defaultValueType) {
			'integer', 'double' => $defaultValue,
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

	#[\Override]
	public function __toString(): string
	{
		$this->bake();

		return $this->queryString ?? '';
	}
}
