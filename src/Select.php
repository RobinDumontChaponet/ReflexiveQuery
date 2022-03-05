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

	public function join(Join $joinType, string $rightTableName, string $columnName, Comparator $comparator = Comparator::EQUAL, ?string $leftTableName = null, ?string $leftColumnName = null): static
	{
		$this->joins[] = [
			'type' => $joinType,
			'leftTableName' => $leftTableName,
			'leftColumnName' => $leftColumnName ?? $columnName,
			'rightTableName' => $rightTableName,
			'rightColumnName' => $columnName,
			'comparator' => $comparator,
			'condition' => null, // WHERE B.key IS NULL
		];

		return $this;
	}
	public function getJoinString(): string
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

			$str .= $join['type']?->value . ' ' . static::quote($join['rightTableName']) . ' ON ' . static::quote($leftTableName . '.' . $join['leftColumnName']) . ' ' . $join['comparator']->value . ' ' . static::quote($join['rightTableName'] . '.' . $join['rightColumnName']).' ';
		}

		return $str;
	}

	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		$this->parameters = [];
		$this->index = 0;

		$this->queryString = $this->command. (empty($this->commandEnd) ? ' ' : '');
		$this->queryString.= $this->getColumnsString();
		$this->queryString.= !empty($this->commandEnd) ? $this->commandEnd.' ' : '';
		$this->queryString.= $this->getFromString();

		$this->queryString.= $this->getJoinString();

		$this->queryString.= $this->getWhereString();
		$this->queryString.= $this->getOrderString();
		$this->queryString.= $this->getLimitOffsetString();
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
