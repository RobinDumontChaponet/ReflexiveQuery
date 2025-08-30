<?php

declare(strict_types=1);

namespace Reflexive\Query;

use Reflexive\Core\Comparator;
// use Reflexive\Core\Condition;
use Reflexive\Query\ConditionGroup;

abstract class Composed extends Simple
{
	protected const DEFAULTCOLUMNS = '*';

	protected array $columns = [];

	/** add ` around tables and columns names  if set to true */
	public bool $quoteNames = true;
	public bool $prettify = false;
	protected array $tables = [];
	protected ?ConditionGroup $conditionGroup = null;

	protected array $groups = [];
	protected array $orders = [];
	protected ?int $limit = null;
	protected ?int $offset = null;
	protected array $parameters = [];
	protected array $joins = [];

	protected function __construct(
		protected string $command,
		array|string|null $columns = [],
		protected string $commandEnd = '',
	) {
		parent::__construct();
		$this->setColumns($columns);
	}

	// abstract protected function bake(): void;
	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		$this->parameters = [];

		$this->queryString = $this->command. (empty($this->commandEnd) ? ' ' : '');
		$this->queryString.= $this->getColumnsString();
		$this->queryString.= !empty($this->commandEnd) ? $this->commandEnd.' ' : '';

		if($this->prettify)
			$this->queryString.= PHP_EOL;

		$this->queryString.= $this->getFromString();

		$this->queryString.= $this->getJoinString();

		$this->queryString.= $this->getWhereString();
		$this->queryString.= $this->getGroupString();
		$this->queryString.= $this->getOrderString();
		$this->queryString.= $this->getLimitOffsetString();

		if($this->prettify)
			$this->queryString = rtrim($this->queryString, PHP_EOL);
	}

	/**
	 * return a PDOStatement using $pdo database connection
	 * @throws \TypeError if for whatever reason PDO->prepare do not return a PDOStatement
	 * @throws \DomainException if $queryString is empty
	 */
	public function prepare(\PDO $pdo): \PDOStatement
	{
		$this->bake();
		$statement = parent::prepare($pdo);

		foreach($this->parameters as $key => $value) {
			$statement->bindValue($key, $value);
		}

		return $statement;
	}

	// Builder
	public static function select(array|string|null $columns = []): Select
	{
		return new Select($columns);
	}

	public static function insert(array|string|null $columns = []): Insert
	{
		return new Insert($columns);
	}

	public static function update(array|string|null $columns = []): Update
	{
		return new Update($columns);
	}

	public static function delete(): Delete
	{
		return new Delete();
	}

	public static function show(array|string|null $columns = []): Show
	{
		return new Show($columns);
	}

	public static function call(): Call
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

	/** add quotes at the right place if necessary */
	protected function quoteName(string $string): string
	{
		if($this->quoteNames)
			return \Reflexive\Core\Strings::quote($string);
		else
			return $string;
	}

	protected function getColumnsString(): string
	{
		if(empty($this->columns))
			return static::DEFAULTCOLUMNS;

		$str = '';
		foreach($this->columns as $key => $column) {
			$str.= $this->quoteName($column);

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
	public function into(array|string|null $tables = null): static
	{
		return $this->from($tables);
	}
	protected function getFromString(): string
	{
		if(empty($this->tables))
			return '';

		$str = ' FROM ';
		foreach($this->tables as $key => $table) {
			$str.= $this->quoteName($table);

			if(is_string($key))
				$str.= ' '.$key;

			$str.= ', ';
		}

		return rtrim($str, ', ') .($this->prettify ? PHP_EOL : '');
	}

	// where
	public function where(Condition|ConditionGroup $condition): static
	{
		$this->queryString = null;

		if(empty($this->conditionGroup))
			$this->conditionGroup = new ConditionGroup();

		$this->conditionGroup->where($condition);

		return $this;
	}

	public function getConditionGroup(): ?ConditionGroup
	{
		return $this->conditionGroup;
	}

	public function hasConditions(): bool
	{
		return $this->conditionGroup?->hasConditions() ?? false;
	}

	public function conditionCount(): int
	{
		return $this->conditionGroup?->count() ?? 0;
	}

	protected function getWhereString(): string
	{
		if(!$this->conditionGroup?->hasConditions())
			return '';

		$index = 0;
		$prettify = $this->prettify ? 0 : -1;
		$str = ' WHERE ';

		$baked = $this->conditionGroup->bake($index, $this->quoteNames, null, $prettify);

		$str .= $baked['queryString'];
		$this->parameters += $baked['parameters'];

		return $str .($this->prettify ? PHP_EOL : '');
	}

	// protected function getWhereString(): string
	// {
	// 	if(empty($this->conditions))
	// 		return '';
//
	// 	$str = ' WHERE ';
	// 	foreach($this->conditions as $condition) {
	// 		$conditionStr = '';
	// 		$key = lcfirst(str_replace('.', '', $condition['name']));
//
	// 		if(is_array($condition['value'])) { // we have an array of value, probably for an IN condition or something
	// 			foreach($condition['value'] as $value) {
	// 				$this->parameters[$key.'_'.$this->index] = $value;
	// 				$conditionStr.= ':'.$key.'_'.$this->index++.',';
	// 			}
	// 			$conditionStr = ' ('. rtrim($conditionStr, ',') .') ';
	// 		} elseif($condition['comparator']!= Comparator::NULL && $condition['comparator']!= Comparator::NOTNULL) { // we have a simple value
	// 			$this->parameters[$key.'_'.$this->index] = $condition['value'];
	// 			$conditionStr = ' :'.$key.'_'.$this->index++.' ';
	// 		}
//
	// 		$str .= $condition['operator']?->value.' '.$this->quoteName($condition['name']).' '.$condition['comparator']?->value.$conditionStr;
	// 	}
//
	// 	return $str .' ';
	// }

	public function and(Condition|ConditionGroup|null $condition = null): static
	{
		if(empty($this->conditionGroup))
			$this->conditionGroup = new ConditionGroup();

		$this->conditionGroup?->and($condition);

		return $this;
	}

	public function or(Condition|ConditionGroup|null $condition = null): static
	{
		if(empty($this->conditionGroup))
			$this->conditionGroup = new ConditionGroup();

		$this->conditionGroup?->or($condition);

		return $this;
	}

	// joins
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
					$leftTableName = $this->quoteName(array_values($this->tables)[0]);
			}

			if(empty($leftTableName))
				throw new \TypeError('Cannot join without left table name');

			$str .= $join['type']?->value . ' ' . $this->quoteName($join['rightTableName']) . ' ON ' . $this->quoteName($leftTableName . '.' . $join['leftColumnName']) . ' ' . $join['comparator']->value . ' ' . $this->quoteName($join['rightTableName'] . '.' . $join['rightColumnName']).' ';
		}

		return $str .($this->prettify ? PHP_EOL : '');
	}

	// group by
	public function group(string $column): static
	{
		$this->queryString = null;

		$this->groups[] = [
			'column' => trim(htmlspecialchars(htmlentities(strip_tags(addcslashes($column, '%_')), ENT_NOQUOTES, 'UTF-8'))),
		];

		return $this;
	}
	protected function getGroupString(): string
	{
		if(empty($this->groups))
			return '';

		$str = ' GROUP BY ';

		foreach($this->groups as $group) {
			$str.= $this->quoteName($group['column']).', ';
		}

		return rtrim($str, ', ').' ' .($this->prettify ? PHP_EOL : '');
	}
	public function isGrouped(): bool
	{
		return !empty($this->groups);
	}

	// order by
	public function order(string $column, Direction $direction = Direction::ASC, bool $nullable = false): static
	{
		$this->queryString = null;

		$this->orders[] = [
			'column' => trim(htmlspecialchars(htmlentities(strip_tags(addcslashes($column, '%_')), ENT_NOQUOTES, 'UTF-8'))),
			'direction' => $direction,
			'nullable' => $nullable
		];

		return $this;
	}
	protected function getOrderString(): string
	{
		if(empty($this->orders))
			return '';

		$str = ' ORDER BY ';
		if(count($this->orders) == 3 && $this->orders[0]['nullable']) {
			$str.= 'IFNULL(';

			$str.= $this->quoteName($this->orders[0]['column']).', ';
			$str.= $this->quoteName($this->orders[1]['column']);
			$str.= ') '.$this->orders[1]['direction']->value. ' ';

			return $str .($this->prettify ? PHP_EOL : '');
		}

		foreach($this->orders as $order) {
			$str.= $this->quoteName($order['column']);

			if($order['nullable']) {
				$str.= ' IS NULL ' .( $order['direction']->value);
			} else {
				$str.= ' '.$order['direction']->value;
			}
			$str.= ', ';
		}

		return rtrim($str, ', ').' ' .($this->prettify ? PHP_EOL : '');
	}
	public function isOrdered(): bool
	{
		return !empty($this->orders);
	}

	// limit
	public function limit(?int $limit = null): static
	{
		$this->queryString = null;

		if($limit < 0)
			throw new \TypeError('Invalid limit ('. $limit. '). Cannot be negative.');

		$this->limit = $limit;

		return $this;
	}
	public function getLimit(): ?int
	{
		return $this->limit;
	}
	public function offset(?int $offset = null): static
	{
		$this->queryString = null;

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

		$str = 'LIMIT '. ($this->limit ?? '') .' ';
		if(!empty($this->offset))
			$str.= 'OFFSET '. $this->offset .' ';

		return $str;
	}

	public function __toString(): string
	{
		$this->bake();

		if(!empty($this->queryString)) {
			$str = parent::__toString() . '; '.PHP_EOL.'/* {';
			foreach($this->parameters as $key => $value) {
				$str.= PHP_EOL."\t".$key.' => '.$value.', ';
			}

			return $str .PHP_EOL.'} */ ';
		}

		return '';
	}
}
