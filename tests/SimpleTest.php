<?php

declare(strict_types=1);

use Reflexive\Query\Simple;

final class SimpleTest extends PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$instance = new Simple();

		$this->assertIsInt($instance::$prepareCount);
		$this->assertEquals(
			0,
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
		$value = 'test';
		$instance = new Simple($value);

		$this->assertEquals(
			$value,
			(string)$instance
		);
	}

	public function testPrepare()
	{
		$queryString = 'SELECT 1 WHERE false';
		$pdo = new PDO(
			'sqlite:',
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
		// public static function read(\PDOStatement $statement, string $key): mixed

		$queryString = 'SELECT 1';
		$pdo = new PDO(
			'sqlite:',
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
		// public static function read(\PDOStatement $statement, string $key): mixed

		$queryString = 'SELECT 1 WHERE false';
		$pdo = new PDO(
			'sqlite:',
		);

		$instance = new Simple($queryString);
		$statement = $instance->prepare($pdo);

		$read = Simple::read($statement, '1');

		$this->assertNull(
			$read
		);
	}
}
