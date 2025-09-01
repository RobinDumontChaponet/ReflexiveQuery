<?php

declare(strict_types=1);

namespace Reflexive\Query;

use \Reflexive\Core\Strings;

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
		$str.= Strings::quote($name) .' FOREIGN KEY (';
		$str.= Strings::quote($key) .') REFERENCES ';
		$str.= Strings::quote($this->referencedTableName) .' (';
		$str.= Strings::quote($this->referencedKey) .') ';
		$str.= 'ON DELETE '. $this->onDelete->value;
		$str.= 'ON UPDATE '. $this->onUpdate->value;

		return $str;
	}
}
