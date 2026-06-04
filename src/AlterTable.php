<?php

declare(strict_types=1);

namespace Reflexive\Query;

use \Reflexive\Core\Strings;

class AlterTable extends TableQuery
{
	protected array $actions = [];

	#[\Override]
	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		if(empty($this->actions))
			throw new \TypeError('No table actions.');

		$this->queryString = 'ALTER TABLE '.Strings::quote($this->name).' ';
		$this->queryString.= implode(', ', $this->actions).';';
	}

	public function addColumn(string $name, string $type, bool $isPrimary = false, bool|null $nullable = null, mixed $defaultValue = null, ?ColumnExtra $extra = null, ?string $after = null, bool $first = false): static
	{
		$this->queryString = null;

		$this->actions[] = 'ADD COLUMN '.$this->getColumnString(
			$this->addColumnDefinition($name, $type, $isPrimary, $nullable, $defaultValue, $extra)
		).$this->getColumnPositionString($after, $first);

		if($isPrimary)
			$this->addPrimary($name);

		return $this;
	}

	public function modifyColumn(string $name, string $type, bool $isPrimary = false, bool|null $nullable = null, mixed $defaultValue = null, ?ColumnExtra $extra = null, ?string $after = null, bool $first = false): static
	{
		$this->queryString = null;

		$this->actions[] = 'MODIFY COLUMN '.$this->getColumnString(
			$this->addColumnDefinition($name, $type, $isPrimary, $nullable, $defaultValue, $extra)
		).$this->getColumnPositionString($after, $first);

		if($isPrimary)
			$this->addPrimary($name);

		return $this;
	}

	public function changeColumn(string $oldName, string $newName, string $type, bool $isPrimary = false, bool|null $nullable = null, mixed $defaultValue = null, ?ColumnExtra $extra = null, ?string $after = null, bool $first = false): static
	{
		$this->queryString = null;

		$this->actions[] = 'CHANGE COLUMN '.Strings::quote(trim($oldName)).' '.$this->getColumnString(
			$this->addColumnDefinition($newName, $type, $isPrimary, $nullable, $defaultValue, $extra)
		).$this->getColumnPositionString($after, $first);

		if($isPrimary)
			$this->addPrimary($newName);

		return $this;
	}

	public function renameColumn(string $oldName, string $newName): static
	{
		$this->queryString = null;

		$this->actions[] = 'RENAME COLUMN '.Strings::quote(trim($oldName)).' TO '.Strings::quote(trim($newName));

		return $this;
	}

	public function dropColumn(string $name): static
	{
		$this->queryString = null;

		$this->actions[] = 'DROP COLUMN '.Strings::quote(trim($name));

		return $this;
	}

	public function addPrimary(array|string $columns): static
	{
		$this->queryString = null;

		$this->actions[] = 'ADD PRIMARY KEY ('.$this->getColumnListString($columns).')';

		return $this;
	}

	public function dropPrimary(): static
	{
		$this->queryString = null;

		$this->actions[] = 'DROP PRIMARY KEY';

		return $this;
	}

	public function addConstraint(string $name, string $key, string $referencedTableName, string $referencedKey, ConstraintAction $onDelete = ConstraintAction::noAction, ConstraintAction $onUpdate = ConstraintAction::noAction): static
	{
		$this->queryString = null;

		$this->actions[] = 'ADD '.$this->getConstraintString(
			$this->addConstraintDefinition($name, $key, $referencedTableName, $referencedKey, $onDelete, $onUpdate)
		);

		return $this;
	}

	public function dropConstraint(string $name): static
	{
		$this->queryString = null;

		$this->actions[] = 'DROP FOREIGN KEY '.Strings::quote(trim($name));

		return $this;
	}

	public function renameTo(string $name): static
	{
		$this->queryString = null;

		$this->actions[] = 'RENAME TO '.Strings::quote(trim($name));

		return $this;
	}

	protected function getColumnPositionString(?string $after, bool $first): string
	{
		if($first)
			return ' FIRST';

		if(!empty($after))
			return ' AFTER '.Strings::quote(trim($after));

		return '';
	}

}
