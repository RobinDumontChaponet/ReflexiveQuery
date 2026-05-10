<?php

declare(strict_types=1);

use Reflexive\Core\Database;
use Reflexive\Query\Condition;
use Reflexive\Query\Delete;
use Reflexive\Query\Insert;
use Reflexive\Query\Update;

final class PushQueriesTest extends PHPUnit\Framework\TestCase
{
	private function createDatabase(): Database
	{
		$database = new Database('sqlite::memory:');
		$database->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT, active INTEGER)');
		$database->exec("INSERT INTO users (email, active) VALUES ('old@example.com', 1)");
		$database->exec("INSERT INTO users (email, active) VALUES ('remove@example.com', 0)");

		return $database;
	}

	public function testInsertPersistsSetValues()
	{
		// Verifies INSERT uses set values as bound parameters.
		$database = $this->createDatabase();
		$query = (new Insert())
			->into('users')
			->set('email', 'new@example.com')
			->set('active', 1);

		$query->prepare($database)->execute();

		$this->assertSame(
			'new@example.com',
			$database->query('SELECT email FROM users WHERE id = 3')->fetchColumn()
		);
		$this->assertSame(['email', 'active'], $query->willSet());
	}

	public function testUpdateAppliesSetValuesToMatchingRows()
	{
		// Verifies UPDATE combines set values with a WHERE condition.
		$database = $this->createDatabase();
		$query = (new Update())
			->into('users')
			->set('email', 'updated@example.com')
			->where(Condition::EQUAL('id', 1));

		$query->prepare($database)->execute();

		$this->assertSame(
			'updated@example.com',
			$database->query('SELECT email FROM users WHERE id = 1')->fetchColumn()
		);
	}

	public function testDeleteRemovesMatchingRows()
	{
		// Verifies DELETE applies a WHERE condition to remove matching rows.
		$database = $this->createDatabase();
		$query = (new Delete())
			->from('users')
			->where(Condition::EQUAL('active', 0));

		$query->prepare($database)->execute();

		$this->assertSame(
			1,
			(int)$database->query('SELECT COUNT(*) FROM users')->fetchColumn()
		);
	}
}
