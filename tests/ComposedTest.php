<?php

declare(strict_types=1);

use Reflexive\Query\Composed;

final class ComposedTest extends PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$command = 'SELECT';
		$instance = new Composed($command);

		$reflection = new ReflectionClass(Composed::class);
		$this->assertSame(
			$command,
			$reflection->getProperty('command')->getValue($instance)
		);
	}

	public function testToString()
	{
		$command = 'SELECT';
		$instance = new Composed($command);

		$this->assertEquals(
			$command,
			(string)$instance
		);
	}
}
