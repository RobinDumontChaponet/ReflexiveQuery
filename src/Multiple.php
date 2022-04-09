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
}
