<?php

declare(strict_types=1);

namespace Reflexive\Query;

use Reflexive\Core\Comparator;

enum Operator: string {
	case AND = ' AND ';
	case OR = ' OR ';
}

enum Direction: string {
	case ASC = ' ASC ';
	case DESC = ' DESC ';
}

class Composed extends Simple
{
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

	protected function __construct(string $command, string $end = '')
	{
		$this->command = $command;
		$this->commandEnd = $end;
	}

	// abstract protected function bake(): void;
	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

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

		return parent::prepare($pdo);
	}

	// Builder
	public static function select(?array $columns = []): static
	{
		return new Select($columns);
	}

	public static function create(?array $columns = []): static
	{
		$object = new self('CREATE');
		$object->setColumns($columns);

		return $object;
	}

	public static function update(?array $columns = []): static
	{
		$object = new self('UPDATE');
		$object->setColumns($columns);

		return $object;
	}

	public static function count(?array $columns = []): static
	{
		$object = new self('SELECT COUNT(', ')');

		$object->setColumns($columns);
		$object->quoteColumns = true;

		return $object;
	}

	public static function delete(): static
	{
		return new Delete();
	}

	public static function show(?array $columns = []): static
	{
		return new Show($columns);
	}

	public static function call(): static
	{
		return new Call();
	}

	// columns
	protected function setColumns(array $columns = []): void
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
	protected function getColumnsString(): string
	{
		if(empty($this->columns))
			return '*';

		$str = '';
		foreach($this->columns as $key => $column) {
			if($this->quoteColumns && !str_starts_with($column, "\u{0060}"))
				$str.= "\u{0060}".$column."\u{0060}";
			else
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
			if(!str_starts_with($table, "\u{0060}"))
				$str.= "\u{0060}".$table."\u{0060}";
			else
				$str.= $table;

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
			throw new \TypeError('Condition added to query chain without operator before last condition.');

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
		$this->parameters = [];
		$this->index = 0;

		if(empty($this->conditions))
			return '';

		$str = ' WHERE ';
		foreach($this->conditions as $condition) {
			$conditionStr = '';

			if(is_array($condition['value'])) { // we have an array of value, probably for an IN condition or something
				foreach($condition['value'] as $value) {
					$this->parameters[$condition['name'].'_'.$this->index] = $value;
					$conditionStr.= ':'.$condition['name'].'_'.$this->index++.',';
				}
				$conditionStr = ' ('. rtrim($conditionStr, ',') .') ';
			} else { // we have a simple value
				$this->parameters[$condition['name'].'_'.$this->index] = $condition['value'];
				$conditionStr = ' :'.$condition['name'].'_'.$this->index++.' ';
			}
			$str .= $condition['operator']?->value." \u{0060}".str_replace("\u{0060}", "\u{0060}\u{0060}", $condition['name'])."\u{0060} ".$condition['comparator']?->value.$conditionStr;
		}

		return $str;
	}

	public function and(...$where): static
	{
		$this->nextOperator = Operator::AND;

		if(!empty($where))
			$this->where(...$where);

		return $this;
	}

	public function or(...$where): static
	{
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

		$str = 'ORDER BY ';
		foreach($this->orders as $order) {
			$str.= "\u{0060}".$order['column']."\u{0060} ".$order['direction']->value.', ';
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
	public function offset(int $offset = null): static
	{
		$this->queryString = null;
		$this->nextOperator = null;

		if($offset < 0)
			throw new \TypeError('Invalid offset ('. $offset. '). Cannot be negative.');

		$this->offset = $offset;

		return $this;
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
