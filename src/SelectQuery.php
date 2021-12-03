<?php

declare(strict_types=1);

namespace Reflexive\Core;

class SelectQuery extends ComposedQuery
{
	public function __construct(?array $columns = [])
	{
		parent::__construct('SELECT');
		$this->setColumns($columns);
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
