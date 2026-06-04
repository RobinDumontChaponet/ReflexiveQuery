<?php

declare(strict_types=1);

use Reflexive\Core\Comparator;
use Reflexive\Core\Database;
use Reflexive\Query\Composed;
use Reflexive\Query\Condition;
use Reflexive\Query\Direction;
use Reflexive\Query\Join;
use Reflexive\Query\Select;

final class QueryBuilderTestCache implements Psr\SimpleCache\CacheInterface
{
	public array $values = [];
	public array $ttls = [];

	public function get(string $key, mixed $default = null): mixed
	{
		return $this->values[$key] ?? $default;
	}

	public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
	{
		$this->values[$key] = $value;
		$this->ttls[$key] = $ttl;

		return true;
	}

	public function delete(string $key): bool
	{
		unset($this->values[$key], $this->ttls[$key]);

		return true;
	}

	public function clear(): bool
	{
		$this->values = [];
		$this->ttls = [];

		return true;
	}

	public function getMultiple(iterable $keys, mixed $default = null): iterable
	{
		foreach($keys as $key)
			yield $key => $this->get($key, $default);
	}

	public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
	{
		foreach($values as $key => $value)
			$this->set($key, $value, $ttl);

		return true;
	}

	public function deleteMultiple(iterable $keys): bool
	{
		foreach($keys as $key)
			$this->delete($key);

		return true;
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->values);
	}
}

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

	public function testMutationAfterRenderingRebuildsQuery()
	{
		// Verifies mutating a rendered query invalidates the cached SQL string.
		$query = (new Select('id'))
			->from('users');

		(string)$query;
		$query->join(Join::left, 'profiles', 'id');
		$query->and(Condition::EQUAL('active', 1));

		$sql = (string)$query;

		$this->assertStringContainsString('LEFT JOIN `profiles`', $sql);
		$this->assertStringContainsString('WHERE `active` = :active_0', $sql);
	}

	public function testLimitZeroIsRendered()
	{
		// Verifies a zero limit is preserved instead of treated as absent.
		$query = (new Select('id'))
			->from('users')
			->limit(0);

		$this->assertStringContainsString('LIMIT 0', (string)$query);
	}

	public function testExplainBakesSelectBeforePreparingStatement()
	{
		// Verifies EXPLAIN prepares the current SELECT query.
		$database = $this->createDatabase();
		$query = (new Select('id'))
			->from('users')
			->where(Condition::EQUAL('active', 1));

		$statement = $query->explain($database);

		$this->assertStringStartsWith('EXPLAIN SELECT', $statement->queryString);
		$this->assertStringContainsString('WHERE `active` = :active_0', $statement->queryString);
	}

	public function testCacheIdentityIsStableAndParameterSensitive(): void
	{
		// Verifies query identity can key external caches without preparing statements.
		$prepareCount = Select::$prepareCount;
		$first = (new Select('id'))
			->from('users')
			->where(Condition::EQUAL('active', 1))
			->order('id')
			->limit(10);
		$same = (new Select('id'))
			->from('users')
			->where(Condition::EQUAL('active', 1))
			->order('id')
			->limit(10);
		$differentParameter = (new Select('id'))
			->from('users')
			->where(Condition::EQUAL('active', 0))
			->order('id')
			->limit(10);

		$this->assertSame($first->getCacheIdentity(), $same->getCacheIdentity());
		$this->assertNotSame($first->getCacheIdentity(), $differentParameter->getCacheIdentity());
		$this->assertSame($prepareCount, Select::$prepareCount);
	}

	public function testCacheStoresValuesInSharedCacheAndInvalidatesByNamespace(): void
	{
		// Verifies Composed owns shared cache plumbing while callers choose payload semantics.
		$cache = new QueryBuilderTestCache();
		$query = (new Select('id'))->from('users');
		$previousUseCache = Composed::$useCache;
		$previousCache = Composed::$cache;
		$previousCacheTTL = Composed::$cacheTTL;

		try {
			Composed::$useCache = true;
			Composed::$cache = $cache;
			Composed::$cacheTTL = 60;
			Composed::clearCache();

			$key = $query->getCacheKey('model:User', 'db');
			Composed::setCachedValue($key, ['keys' => [1, 2]]);
			Composed::clearLocalCache();

			$this->assertSame(['keys' => [1, 2]], Composed::getCachedValue($key));
			$this->assertSame(60, $cache->ttls[$key]);

			Composed::clearCache('model:User');
			$this->assertNotSame($key, $query->getCacheKey('model:User', 'db'));
			$this->assertNull(Composed::getCachedValue($query->getCacheKey('model:User', 'db')));
		} finally {
			Composed::clearCache();
			Composed::$useCache = $previousUseCache;
			Composed::$cache = $previousCache;
			Composed::$cacheTTL = $previousCacheTTL;
		}
	}
}
