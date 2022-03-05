<?php

// for multiple rowsets

declare(strict_types=1);

namespace Reflexive\Query;

class Multiple extends Simple
{
	function __construct(
		private array $queries = [],
	)
	{}

	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		$this->queryString = '';

		foreach($this->queries as $query) {
			$this->queryString.= rtrim($query->_toString(), '; ').'; ';
		}
	}

	public function prepare(\PDO $pdo): \PDOStatement
	{
		$this->bake();
		return parent::prepare($pdo);
	}

	public function __toString(): string
	{
		return $this->queryString;
	}

	public static function read(\PDOStatement $statement, string $key): mixed
	{
		// Get row data
		if($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
			return $row[$key];
		}

		return null;
	}
}
