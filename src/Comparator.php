<?php

declare(strict_types=1);

namespace Reflexive\Core;

enum Comparator: string {
	case EQUAL = '=';
	case NOTEQUAL = '<>';
	case GREATER = '>';
	case LESS = '<';
	case GREATEROREQUAL = '>=';
	case LESSOREQUAL = '<=';
	case IN = 'IN';
	case BETWEEN = 'BETWEEN';
	case LIKE = 'LIKE';
	case NULL = 'IS NULL';
	case NOTNULL = 'IS NOT NULL';
}

// abstract class Comparator
// {
// 	protected string|int|float|array $value;
// 	protected string $type;
//
// 	public const EQUAL = '=';
// 	public const NOTEQUAL = '<>';
// 	public const GREATER = '>';
// 	public const LESS = '<';
// 	public const GREATEROREQUAL = '>=';
// 	public const LESSOREQUAL = '<=';
// 	public const IN = 'IN';
// 	public const BETWEEN = 'BETWEEN';
// 	public const LIKE = 'LIKE';
// 	public const NULL = 'IS NULL';
// 	public const NOTNULL = 'IS NOT NULL';
//
// 	public static function eq(string|int|float|array $value): static
// 	{
// 		return new EqualComparator(self::EQUAL, $value);
// 	}
//
// 	protected function __construct(string $type, string|int|float|array $value)
// 	{
// 		$this->type = $type;
// 		$this->value = $value;
// 	}
//
// 	public function __toString(): string
// 	{
// 		return $this->type, $this->value;
// 	}
// }
//
// class EqualComparator extends Comparator
// {
// 	public function __toString(): string
// 	{
// 		return $this->type, $this->value;
// 	}
// }
