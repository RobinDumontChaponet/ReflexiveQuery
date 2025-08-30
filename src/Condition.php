<?php

declare(strict_types=1);

namespace Reflexive\Query;

use Reflexive\Core\Strings;
use Reflexive\Core\Operator;
use Reflexive\Core\Comparator;

class Condition extends \Reflexive\Core\Condition
{
	public function __construct(
		string $name,
		Comparator $comparator,
		string|int|float|bool|array|null $value = null
	) {
		parent::__construct($name, $comparator);
		$this->value = $value;
	}

	// oh, this is some kind of factory function uh…
	public function and(self $condition): ConditionGroup
	{
		return (new ConditionGroup())
			->where($this)
			->and($condition);
	}
	// oh, this is some kind of factory function uh…
	public function or(self $condition): ConditionGroup
	{
		return (new ConditionGroup())
			->where($this)
			->or($condition);
	}

	public function bake(int &$index, bool $quoteNames = true, ?Operator $operator = null, int &$prettify = 0): array
	{
		// $queryString = ($prettify > 0 ? str_repeat("\t", $prettify) : '');
		$queryString = $operator !== null ? $operator->value.($prettify > 0 ? PHP_EOL.str_repeat("\t", $prettify) : '') : '';
		$queryString.= $quoteNames ? Strings::quote($this->name) : $this->name;
		$queryString.= ' '.$this->comparator?->value;
		$parameters = [];

		$key = lcfirst(str_replace('.', '', $this->name));

		if(is_array($this->value)) { // we have an array of value, probably for an IN condition or something
			$subIndex = 0;
			$subString = '';

			foreach($this->value as $value) {
				$parameters[$key.'_'.$index.'_'.$subIndex] = $value;
				$subString.= ':'.$key.'_'.$index.'_'.$subIndex++.', ';
			}
			$queryString.= ' ('.($prettify ? PHP_EOL.str_repeat("\t", $prettify+1) : ''). rtrim($subString, ', ') .($prettify > 0 ? PHP_EOL.str_repeat("\t", $prettify) : '').') ';
		} elseif($this->comparator != Comparator::NULL && $this->comparator != Comparator::NOTNULL) { // we have a simple value
			$parameters[$key.'_'.$index] = $this->value;
			$queryString.= ' :'.$key.'_'.$index;
		}

		return [
			'queryString' => $queryString .($prettify > 0 ? PHP_EOL : ''),
			'parameters' => $parameters,
		];
	}
}
