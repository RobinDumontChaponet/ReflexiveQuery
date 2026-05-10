<?php

declare(strict_types=1);

use Reflexive\Core\Comparator;
use Reflexive\Core\Database;
use Reflexive\Query\Condition;
use Reflexive\Query\Direction;
use Reflexive\Query\Join;
use Reflexive\Query\Select;

final class QueryBuilderTest extends PHPUnit\Framework\TestCase
{
	private function createDatabase(): Database
	{
		$database = new Database('sqlite::memory:');
		$database->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT, active INTEGER, deleted_at TEXT, created_at TEXT, published_at TEXT)');
		$database->exec("INSERT INTO users (id, email, active, deleted_at, created_at, published_at) VALUES (1, 'a@example.com', 1, NULL, '2024-01-01', NULL)");
		$database->exec("INSERT INTO users (id, email, active, deleted_at, created_at, published_at) VALUES (2, 'b@example.net', 1, '2024-02-01', '2024-01-02', '2024-01-10')");
		$database->exec("INSERT INTO users (id, email, active, deleted_at, created_at, published_at) VALUES (3, 'c@example.com', 1, NULL, '2024-01-03', '2024-01-11')");

		return $database;
	}

	public function testSelectBindsWhereParametersAndReturnsMatchingRows()
	{
		// Verifies SELECT binds scalar conditions and applies ordering and limits.
		$database = $this->createDatabase();
		$query = (new Select(['id', 'email']))
			->from('users')
			->where(Condition::EQUAL('active', 1))
			->and(Condition::LIKE('email', '%@example.com'))
			->order('id', Direction::DESC)
			->limit(1);

		$statement = $query->prepare($database);
		$statement->execute();

		$this->assertSame(
			[
				[
					'id' => 3,
					'email' => 'c@example.com',
				],
			],
			$statement->fetchAll(PDO::FETCH_ASSOC)
		);
		$this->assertStringContainsString('WHERE (`active` = :active_0 AND `email` LIKE :email_1)', $statement->queryString);
	}

	public function testSelectSupportsArrayAndNullConditions()
	{
		// Verifies IN arrays and IS NULL conditions render and execute together.
		$database = $this->createDatabase();
		$query = (new Select('id'))
			->from('users')
			->where(Condition::IN('id', [1, 3]))
			->and(Condition::NULL('deleted_at'))
			->order('id');

		$statement = $query->prepare($database);
		$statement->execute();

		$this->assertSame(
			[
				['id' => 1],
				['id' => 3],
			],
			$statement->fetchAll(PDO::FETCH_ASSOC)
		);
	}

	public function testJoinAndNullableOrderingAreRendered()
	{
		// Verifies joins infer the left table and nullable ordering uses COALESCE.
		$query = (new Select(['users.id', 'posts.id']))
			->from('users')
			->join(Join::left, 'posts', 'id', Comparator::EQUAL)
			->order('published_at', Direction::DESC, true)
			->order('created_at', Direction::DESC);

		$sql = (string)$query;

		$this->assertStringContainsString('LEFT JOIN `posts` ON `users`.`id` = `posts`.`id`', $sql);
		$this->assertStringContainsString('ORDER BY COALESCE(`published_at`, `created_at`)', $sql);
	}
}
