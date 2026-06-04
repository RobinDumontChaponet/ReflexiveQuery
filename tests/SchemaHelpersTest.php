<?php

declare(strict_types=1);

use Reflexive\Query\Column;
use Reflexive\Query\ColumnExtra;
use Reflexive\Query\Constraint;
use Reflexive\Query\ConstraintAction;
use Reflexive\Query\CreateTable;
use Reflexive\Query\AlterTable;
use Reflexive\Query\DropTable;
use Reflexive\Query\RenameTable;
use Reflexive\Query\TruncateTable;

final class SchemaHelpersTest extends PHPUnit\Framework\TestCase
{
	public function testColumnRendersTypeDefaultNullabilityAndExtra()
	{
		// Verifies Column renders its public schema attributes into SQL.
		$column = new Column(
			'email',
			'VARCHAR(255)',
			nullable: false,
			defaultValue: 'pending',
			extra: ColumnExtra::onUpdateCurrent,
		);

		$this->assertSame(
			"`email` VARCHAR(255) NOT NULL DEFAULT 'pending' ON UPDATE current_timestamp()",
			(string)$column
		);
	}

	public function testConstraintRendersForeignKeyActions()
	{
		// Verifies Constraint renders both ON DELETE and ON UPDATE actions.
		$constraint = new Constraint(
			'users',
			'id',
			ConstraintAction::setNull,
			ConstraintAction::cascade,
		);

		$this->assertSame(
			'CONSTRAINT `posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
			$constraint->asString('posts_user', 'user_id')
		);
	}

	public function testCreateTableRendersColumnsPrimaryKeyAndConstraints()
	{
		// Verifies CreateTable renders columns, primary keys, and foreign keys.
		$query = (new CreateTable('posts'))
			->addColumn('id', 'INTEGER', isPrimary: true, extra: ColumnExtra::autoIncrement)
			->addColumn('user_id', 'INTEGER', nullable: false)
			->addColumn('title', 'VARCHAR(255)', defaultValue: 'Untitled')
			->addConstraint('posts_user', 'user_id', 'users', 'id', ConstraintAction::cascade, ConstraintAction::restrict);

		$this->assertSame(
			"CREATE TABLE `posts` (`id` INTEGER NOT NULL AUTO_INCREMENT, `user_id` INTEGER NOT NULL, `title` VARCHAR(255) DEFAULT 'Untitled', PRIMARY KEY (`id`), CONSTRAINT `posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;",
			(string)$query
		);
	}

	public function testCreateTableRequiresAtLeastOneColumn()
	{
		// Verifies CreateTable rejects rendering a table with no columns.
		$this->expectException(TypeError::class);

		(string)new CreateTable('empty_table');
	}

	public function testAlterTableRendersColumnAndConstraintActions()
	{
		// Verifies AlterTable can combine common column, key, and rename actions.
		$query = (new AlterTable('posts'))
			->addColumn('slug', 'VARCHAR(255)', nullable: false, after: 'title')
			->modifyColumn('title', 'VARCHAR(500)', nullable: false)
			->renameColumn('user_id', 'author_id')
			->dropColumn('legacy_id')
			->addConstraint('posts_author', 'author_id', 'users', 'id', ConstraintAction::cascade, ConstraintAction::restrict)
			->dropConstraint('posts_user')
			->renameTo('articles');

		$this->assertSame(
			'ALTER TABLE `posts` ADD COLUMN `slug` VARCHAR(255) NOT NULL AFTER `title`, MODIFY COLUMN `title` VARCHAR(500) NOT NULL, RENAME COLUMN `user_id` TO `author_id`, DROP COLUMN `legacy_id`, ADD CONSTRAINT `posts_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT, DROP FOREIGN KEY `posts_user`, RENAME TO `articles`;',
			(string)$query
		);
	}

	public function testAlterTableRendersPrimaryKeyActions()
	{
		// Verifies primary keys render as separate ALTER TABLE actions.
		$query = (new AlterTable('posts'))
			->addColumn('id', 'INTEGER', isPrimary: true, extra: ColumnExtra::autoIncrement)
			->dropPrimary()
			->addPrimary(['site_id', 'slug']);

		$this->assertSame(
			'ALTER TABLE `posts` ADD COLUMN `id` INTEGER NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`), DROP PRIMARY KEY, ADD PRIMARY KEY (`site_id`, `slug`);',
			(string)$query
		);
	}

	public function testAlterTableRequiresAtLeastOneAction()
	{
		// Verifies AlterTable rejects rendering without queued table actions.
		$this->expectException(TypeError::class);

		(string)new AlterTable('posts');
	}

	public function testOtherTableActionsRender()
	{
		// Verifies single-purpose table actions render with identifier quoting.
		$this->assertSame('DROP TABLE IF EXISTS `posts`;', (string)new DropTable('posts', ifExists: true));
		$this->assertSame('RENAME TABLE `posts` TO `articles`;', (string)new RenameTable('posts', 'articles'));
		$this->assertSame('TRUNCATE TABLE `posts`;', (string)new TruncateTable('posts'));
	}

	public function testTableActionsBakeBeforePrepare()
	{
		// Verifies table actions prepare their generated SQL, not an empty string.
		$pdo = new PDO('sqlite::memory:');

		$statement = (new DropTable('posts', ifExists: true))->prepare($pdo);

		$this->assertSame('DROP TABLE IF EXISTS `posts`;', $statement->queryString);
	}
}
