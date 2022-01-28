<?php

declare(strict_types=1);

namespace Reflexive\Query;

use Reflexive\Core\Comparator;

class Select extends Composed
{
	protected array $joins = [];

	public function __construct(array|string|null $columns = [])
	{
		parent::__construct('SELECT', $columns);
	}

	public function join(Join $joinType, string $rightTableName, string $attributeName, Comparator $comparator = Comparator.EQUAL, ?string $leftTableName = null, ?string $leftAttributeName = null): static
	{
		$this->joins[] = [
			'type' => $joinType,
			'leftTableName' => $leftTableName,
			'leftAttributeName' => $leftAttributeName ?? $attributeName,
			'rightTableName' => $rightTableName,
			'rightAttributeName' => $attributeName,
			'comparator' => $comparator,
			'condition' => null, // WHERE B.key IS NULL
		];

		return $this;
	}
	protected function getJoinString(): string
	{
		if(empty($this->joins))
			return '';

		$str = ' ';

		// LEFT JOIN B ON A.key = B.key
		foreach($this->joins as $join) {
			$leftTableName = $join['leftTableName'];

			if(empty($leftTableName) && count($this->tables) == 1) {
				if(is_string($key = array_keys($this->tables)[0]))
					$leftTableName = $key;
				else
					$leftTableName = static::quote(array_values($this->tables)[0]);
			}

			if(empty($leftTableName))
				throw new \TypeError('Cannot join without left table name');

			$str .= 'JOIN ' . $join['type']?->value . ' ' . static::quote($join['rightTableName']) . ' ON ' . $leftTableName . '.' . $join['leftAttributeName'] . ' ' . $join['comparator']->value . ' ' . $join['rightTableName'] . '.' . $join['rightAttributeName'];
		}

		return $str;
	}

	public function explain(\PDO $pdo): \PDOStatement
	{
		try {
			$this->bake();
			$this->queryString = 'EXPLAIN '. $this->queryString;

			return parent::prepare($pdo);
		} finally {
			$this->queryString = null;
		}
	}
}
