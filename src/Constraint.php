<?php

declare(strict_types=1);

namespace Reflexive\Query;

// UNUSED FOR NOW. May migrate to Reflexive\Models

class Constraint
{
	// , CONSTRAINT `Screen_userId` FOREIGN KEY (`userId`) REFERENCES `User` (`id`) ON DELETE SET NULL ON UPDATE CASCADE)
	function __construct(
		protected readonly string $referencedTableName,
		protected readonly string $referencedKey,
		protected readonly ConstraintAction $onDelete = ConstraintAction::noAction,
		protected readonly ConstraintAction $onUpdate = ConstraintAction::noAction,
	) {}

	public function asString(string $name, string $key): string
	{
		$str = 'CONSTRAINT ';
		$str.= Simple::quote($name) .' FOREIGN KEY (';
		$str.= Simple::quote($key) .') REFERENCES ';
		$str.= Simple::quote($this->referencedTableName) .' (';
		$str.= Simple::quote($this->referencedKey) .') ';
		$str.= 'ON DELETE '. $this->onDelete->value;
		$str.= 'ON UPDATE '. $this->onUpdate->value;

		return $str;
	}
}
