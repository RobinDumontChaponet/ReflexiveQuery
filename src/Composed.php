<?php

declare(strict_types=1);

namespace Reflexive\Query;

use Reflexive\Core\Comparator;

class Composed extends Simple
{
	protected const DEFAULTCOLUMNS = '*';

	protected $command;
	protected $commandEnd = '';
	protected $columns = [];
	protected $quoteColumns = true;
	protected $tables = [];
	protected $conditions = [];
	protected $nextOperator;
	protected $index = 0;
	protected $orders;
	protected $limit;
	protected $offset;
	protected $parameters = [];

	protected function __construct(string $command, array|string|null $columns = [], string $end = '')
	{
		$this->command = $command;
		$this->commandEnd = $end;
		$this->setColumns($columns);
	}

	// abstract protected function bake(): void;
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
		$this->queryString.= $this->getWhereString();
		$this->queryString.= $this->getOrderString();
		$this->queryString.= $this->getLimitOffsetString();
	}

	public function prepare(\PDO $pdo): \PDOStatement
	{
		$this->bake();

		// $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
		$statement = $pdo->prepare($this->queryString, [
			\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL,
		]);
		// $statement->setFetchMode(\PDO::FETCH_OBJ);

		foreach($this->parameters as $key => $value) {
			$statement->bindValue($key, $value);
		}

		return $statement;
	}

	// Builder
	public static function select(array|string|null $columns = []): static
	{
		return new Select($columns);
	}

	public static function insert(array|string|null $columns = []): static
	{
		return new Insert($columns);
	}

	public static function update(array|string|null $columns = []): static
	{
		return new update($columns);
	}

	public static function delete(array|string|null $columns = []): static
	{
		return new Delete($columns);
	}

	public static function show(array|string|null $columns = []): static
	{
		return new Show($columns);
	}

	public static function call(): static
	{
		return new Call();
	}

	// columns
	public function setColumns(array|string|null $columns = []): void
	{
		if(is_array($columns)) {
			foreach($columns as $key => $column) {
				$column = trim($column);

				if(is_string($key))
					$this->columns[$key] = $column;
				else
					$this->columns[] = $column;
			}
		} elseif(!empty($columns)) {
			$this->columns[] = trim($columns);
		} else {
			$this->columns = [];
		}
	}

	protected static function quote(string $string): string
	{
		return preg_replace('/\b((?<!`)[^\s()`\.]+(?![\(`]))\b/i', '`$1`', $string);
	}

	protected function getColumnsString(): string
	{
		if(empty($this->columns))
			return static::DEFAULTCOLUMNS;

		$str = '';
		foreach($this->columns as $key => $column) {
			if($this->quoteColumns && !str_starts_with($column, "\u{0060}")) {
				$str.= static::quote($column);
			} else
				$str.= $column;

			if(is_string($key))
				$str.= ' '.$key;

			$str.= ', ';
		}

		return rtrim($str, ', ');
	}

	// from
	public function from(array|string|null $tables = null): static
	{
		$this->queryString = null;
		$this->nextOperator = null;
		$this->tables = [];

		if(is_array($tables)) {
			foreach($tables as $key => $table) {
				$table = trim($table);

				if(is_string($key))
					$this->tables[$key] = $table;
				else
					$this->tables[] = $table;
			}
		} elseif(!empty($tables)) {
			$this->tables[] = trim($tables);
		} else {
			$this->tables = [];
		}

		return $this;
	}
	protected function getFromString(): string
	{
		if(empty($this->tables))
			return '';

		$str = ' FROM ';
		foreach($this->tables as $key => $table) {
			$str.= static::quote($table);

			if(is_string($key))
				$str.= ' '.$key;

			$str.= ', ';
		}

		return rtrim($str, ', ');
	}

	// where
	public function where(string $name, Comparator $comparator, string|int|float|array $value = null): static
	{
		if(!empty($this->conditions) && empty($this->nextOperator))
			throw new \TypeError('Condition added to query chain without operator before previous condition.');

		$this->queryString = null;

		$this->conditions[] = [
			'name' => trim($name),
			'comparator' => $comparator,
			'value' => $value,
			'operator' => $this->nextOperator,
		];

		$this->nextOperator = null;

		return $this;
	}
	protected function getWhereString(): string
	{
		if(empty($this->conditions))
			return '';

		$str = ' WHERE ';
		foreach($this->conditions as $condition) {
			$conditionStr = '';
			$key = lcfirst(str_replace('.', '', $condition['name']));

			if(is_array($condition['value'])) { // we have an array of value, probably for an IN condition or something
				foreach($condition['value'] as $value) {
					$this->parameters[$key.'_'.$this->index] = $value;
					$conditionStr.= ':'.$key.'_'.$this->index++.',';
				}
				$conditionStr = ' ('. rtrim($conditionStr, ',') .') ';
			} else { // we have a simple value
				$this->parameters[$key.'_'.$this->index] = $condition['value'];
				$conditionStr = ' :'.$key.'_'.$this->index++.' ';
			}



			$str .= $condition['operator']?->value.' '.static::quote($condition['name']).' '.$condition['comparator']?->value.$conditionStr;
		}

		return $str;
	}

	public function and(...$where): static
	{
		if(!empty($this->conditions))
			$this->nextOperator = Operator::AND;

		if(!empty($where))
			$this->where(...$where);

		return $this;
	}

	public function or(...$where): static
	{
		if(!empty($this->conditions))
			$this->nextOperator = Operator::OR;

		if(!empty($where))
			$this->where(...$where);

		return $this;
	}

	// order by
	public function order(string $column, Direction $direction = Direction::ASC): static
	{
		$this->queryString = null;
		$this->nextOperator = null;

		$this->orders[] = [
			'column' => trim(htmlspecialchars(htmlentities(strip_tags(addcslashes($column, '%_')), ENT_NOQUOTES, 'UTF-8'))),
			'direction' => $direction,
		];

		return $this;
	}
	protected function getOrderString(): string
	{
		if(empty($this->orders))
			return '';

		$str = ' ORDER BY ';
		foreach($this->orders as $order) {
			$str.= static::quote($order['column']).' '.$order['direction']->value.', ';
		}

		return rtrim($str, ', '). ' ';
	}

	// limit
	public function limit(int $limit = null): static
	{
		$this->queryString = null;
		$this->nextOperator = null;

		if($limit < 0)
			throw new \TypeError('Invalid limit ('. $limit. '). Cannot be negative.');

		$this->limit = $limit;

		return $this;
	}
	public function getLimit(): ?int
	{
		return $this->limit;
	}
	public function offset(int $offset = null): static
	{
		$this->queryString = null;
		$this->nextOperator = null;

		if($offset < 0)
			throw new \TypeError('Invalid offset ('. $offset. '). Cannot be negative.');

		$this->offset = $offset;

		return $this;
	}
	public function getOffset(): ?int
	{
		return $this->offset;
	}
	protected function getLimitOffsetString(): string
	{
		if(empty($this->limit) && empty($this->offset))
			return '';

		$str = 'LIMIT '. $this->limit .' ';
		if(!empty($this->offset))
			$this->queryString.= 'OFFSET '. $this->offset .' ';

		return $str;
	}

	public function __toString(): string
	{
		$this->bake();

		return $this->queryString;
	}
}
