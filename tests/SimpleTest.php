<?php

declare(strict_types=1);

use Reflexive\Core\Database;
use Reflexive\Query\Simple;

final class SimpleTest extends PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		// Verifies an empty raw query starts with the default state.
		$prepareCount = Simple::$prepareCount;
		$instance = new Simple();

		$this->assertIsInt($instance::$prepareCount);
		$this->assertSame(
			$prepareCount,
			$instance::$prepareCount
		);

		$reflection = new ReflectionClass(Simple::class);
		$this->assertEquals(
			'',
			$reflection->getProperty('queryString')->getValue($instance)
		);
	}

	public function testToString()
	{
		// Verifies raw query strings are returned unchanged.
		$value = 'test';
		$instance = new Simple($value);

		$this->assertEquals(
			$value,
			(string)$instance
		);
	}

	public function testPrepare()
	{
		// Verifies raw SQL is prepared without changing the query string.
		$queryString = 'SELECT 1 WHERE false';
		$pdo = new Database(
			'sqlite::memory:',
		);

		$instance = new Simple($queryString);
		$statement = $instance->prepare($pdo);

		$this->assertInstanceOf(
			PDOStatement::class,
			$statement
		);

		$this->assertEquals(
			$queryString,
			$statement->queryString
		);
	}

	public function testRead()
	{
		// Verifies read returns the first row value for the requested key.
		$queryString = 'SELECT 1';
		$pdo = new Database(
			'sqlite::memory:',
		);

		$instance = new Simple($queryString);
		$statement = $instance->prepare($pdo);

		$read = Simple::read($statement, '1');

		$this->assertEquals(
			1,
			$read
		);
	}

	public function testReadNull()
	{
		// Verifies read returns null when the prepared statement has no row.
		$queryString = 'SELECT 1 WHERE false';
		$pdo = new Database(
			'sqlite::memory:',
		);

		$instance = new Simple($queryString);
		$statement = $instance->prepare($pdo);

		$read = Simple::read($statement, '1');

		$this->assertNull(
			$read
		);
	}

	public function testPrepareRejectsEmptyQueryString()
	{
		// Verifies empty raw queries are rejected before reaching PDO.
		$pdo = new Database(
			'sqlite::memory:',
		);

		$this->expectException(DomainException::class);

		(new Simple())->prepare($pdo);
	}
}
