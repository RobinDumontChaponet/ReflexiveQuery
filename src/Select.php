<?php

declare(strict_types=1);

namespace Reflexive\Query;

use DomainException;

class Select extends Composed
{
	protected array $joins = [];

	public function __construct(array|string|null $columns = [])
	{
		parent::__construct('SELECT', $columns);
	}

	public function explain(\PDO $pdo): \PDOStatement
	{
		if(empty($this->queryString))
			throw new DomainException('Empty query string');

		try {
			$this->bake();
			$this->queryString = 'EXPLAIN '. ($this->queryString ?? '');

			return parent::prepare($pdo);
		} finally {
			$this->queryString = null;
		}
	}
}
