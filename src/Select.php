<?php

declare(strict_types=1);

namespace Reflexive\Query;

class Select extends Composed
{
	public function __construct(array|string|null $columns = [])
	{
		parent::__construct('SELECT', $columns);
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
