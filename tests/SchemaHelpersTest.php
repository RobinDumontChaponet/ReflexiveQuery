<?php

declare(strict_types=1);

use Reflexive\Query\Column;
use Reflexive\Query\ColumnExtra;
use Reflexive\Query\Constraint;
use Reflexive\Query\ConstraintAction;
use Reflexive\Query\CreateTable;

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
}
