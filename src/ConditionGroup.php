<?php

declare(strict_types=1);

namespace Reflexive\Query;

use Reflexive\Core\Operator;

class ConditionGroup extends \Reflexive\Core\ConditionGroup
{
	protected array $parameters = [];

	public function __construct(
		?Condition $firstCondition = null
	) {
		parent::__construct($firstCondition);
	}

	public function bake(int &$index, bool $quoteNames = true, ?Operator $operator = null, int &$prettify = -1): array
	{
		$queryString = '';
		$parameters = [];
		if($prettify > -1 && count($this->conditions) > 1) {
			$prettify++;
		}

		foreach($this->conditions as $conditionArray) {
			$baked = $conditionArray['condition']->bake($index, $quoteNames, $conditionArray['operator'], $prettify);

			$queryString.= ($prettify > 0 ? str_repeat("\t", $prettify) : '').$baked['queryString'];
			$parameters += $baked['parameters'];

			$index++;
		}

		if(count($this->conditions) > 1) {
			$queryString = '('.($prettify > 0 ? PHP_EOL : ''). rtrim($queryString, PHP_EOL) .($prettify > 0 ? PHP_EOL.str_repeat("\t", --$prettify) : '').')';
		}

		return [
			'queryString' => $operator?->value.$queryString,
			'parameters' => $parameters,
		];
	}
}
