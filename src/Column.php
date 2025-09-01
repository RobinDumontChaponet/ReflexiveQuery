<?php

declare(strict_types=1);

namespace Reflexive\Query;

use \Reflexive\Core\Strings;

// UNUSED FOR NOW. May migrate to Reflexive\Models

class Column implements \Stringable
{
	function __construct(
		protected string $name,
		protected string $type,
		protected bool $primary = false,
		protected bool|null $nullable = null,
		protected mixed $defaultValue = null,
		protected ?ColumnExtra $extra = null,
		protected ?Constraint $constraint = null,
	) {}

	public function getName(): string
	{
		return $this->name;
	}

	public function setDefaultValue(mixed $defaultValue): void
	{
		$this->defaultValue = $defaultValue;
	}

	public function setExtra(?ColumnExtra $extra): void
	{
		$this->extra = $extra;
	}

	public function hasConstraint(): bool
	{
		return !empty($this->constraint);
	}
	public function getConstraint(): ?Constraint
	{
		return $this->constraint;
	}
	public function setConstraint(?Constraint $constraint): void
	{
		$this->constraint = $constraint;
	}

	public function __toString(): string
	{
		$str = Strings::quote($this->name) .' ';
		$str.= $this->type;
		$str.= $this->primary ? ' PRIMARY KEY ':'';
		$str.= $this->nullable === false || $this->primary ? ' NOT NULL' : '';
		if(isset($this->defaultValue)) {
			$str.= ' DEFAULT ';
			$defaultValue = $this->defaultValue;
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
		$str.= ' '.$this->extra?->value;

		return rtrim($str, ' ');
	}
}
