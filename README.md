# ReflexiveQuery

$$ {{∀ x ∈ X : x R x}} $$

---

A lightweight SQL query builder built on top of `reflexive/core`.

The package focuses on producing prepared statements with bound parameters instead of interpolated values.

## Requirements
- PHP `^8.4`
- `reflexive/core`

## Installation
```bash
composer require reflexive/query
```

## Main entry points
The builder is centred around `Reflexive\Query\Composed` and the concrete query types it creates:

- `Select`
- `Insert`
- `Update`
- `Delete`
- `Show`

Helper classes:
- `Condition` and `ConditionGroup` for `WHERE` trees
- `Join` and `Direction` enums
- `CreateTable` for basic DDL
- `Simple` for raw SQL that still uses `PDO::prepare()`

## Basic usage
```php
use Reflexive\Query\Select;
use Reflexive\Query\Condition;
use Reflexive\Query\Direction;

$query = new Query\Select(['u.id', 'u.email'])
	->from(['u' => 'users'])
	->where(Condition::EQUAL('u.active', true))
	->and(Condition::LIKE('u.email', '%@example.com'))
	->order('u.id', Direction::DESC)
	->limit(20)
	->offset(0);

$stmt = $query->prepare($pdo);
$stmt->execute();
$rows = $stmt->fetchAll();
```

`prepare()` calls `PDO::prepare()` and binds all collected parameters with `bindValue()`.

## Building conditions
`Condition` extends the core condition factory methods:

```php
use Reflexive\Query\Condition;

->where(Condition::EQUAL('users.status', 'published')->and(Condition::IN('users.id', [1, 2, 3])));
```

The generated SQL uses named placeholders:
- scalar values become `:column_0`
- array values become `(:column_0_0, :column_0_1, ...)`
- `NULL` and `NOTNULL` do not bind a value

Nested condition groups are supported through `ConditionGroup`.
Passing a Condition chain inside a Condition essentially builds a ConditionGroup.

## Supported query features
`Composed` currently supports:

- selecting explicit columns or falling back to `*`
- table aliases via associative arrays in `from()` / `into()`
- `WHERE` trees
- `INNER`, `LEFT`, `RIGHT`, and `FULL` joins
- `GROUP BY`
- `ORDER BY`
- `LIMIT` and `OFFSET`
- identifier quoting (`$query->quoteNames`)
- optional pretty-printing (`$query->prettify`)

`Insert` and `Update` use `set($column, $value)` to collect assignments.

```php
use Reflexive\Query\Insert;

$insert = new Insert()
	->from('users')
	->set('email', 'person@example.com')
	->set('active', 1);
```

## Raw queries
Use `Simple` when you already have SQL but still want consistent `prepare()` behaviour:

```php
use Reflexive\Query\Simple;

$query = new Simple('SELECT COUNT(*) AS c FROM users');
$stmt = $query->prepare($pdo);
$count = Simple::read($stmt, 'c');
```

`Simple::format()` can also turn a statement into a basic HTML table for quick debugging.

## DDL helpers
`CreateTable` builds a MySQL-flavoured `CREATE TABLE` statement:

```php
use Reflexive\Query\CreateTable;
use Reflexive\Query\ColumnExtra;

$ddl = new CreateTable('users')
	->addColumn('id', 'BIGINT UNSIGNED', isPrimary: true, extra: ColumnExtra::autoIncrement)
	->addColumn('email', 'VARCHAR(255)', nullable: false);

echo (string) $ddl;
```

It also supports foreign keys through `addConstraint()`, using `ConstraintAction` values for `ON DELETE` / `ON UPDATE`.

## Current limitations
- `Call` exists but throws immediately: it is not implemented.
- `CreateTable` emits MySQL-specific SQL (`ENGINE=INNODB`, `utf8mb4`).
- `Show` disables identifier quoting and is meant for database-specific `SHOW ...` statements.
- The package includes some older classes (`Column`, `Constraint`, `Multiple`) that are marked unused or are only lightly integrated.
