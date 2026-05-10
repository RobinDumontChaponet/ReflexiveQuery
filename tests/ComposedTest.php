<?php

declare(strict_types=1);

use Reflexive\Query\Composed;

final class ConcreteComposedForTest extends Composed
{
	public function __construct(string $command)
	{
		parent::__construct($command);
	}
}

final class ComposedTest extends PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		// Verifies a concrete composed query stores the SQL command.
		$command = 'SELECT';
		$instance = new ConcreteComposedForTest($command);

		$reflection = new ReflectionClass(Composed::class);
		$this->assertSame(
			$command,
			$reflection->getProperty('command')->getValue($instance)
		);
	}

	public function testToString()
	{
		// Verifies composed queries bake the command into string output.
		$command = 'SELECT';
		$instance = new ConcreteComposedForTest($command);

		$this->assertStringStartsWith(
			$command,
			(string)$instance
		);
	}
}
