<?php

declare(strict_types=1);

namespace Reflexive\Query;

use \Reflexive\Core\Strings;

class RenameTable extends TableQuery
{
	public function __construct(
		string $name,
		protected string $newName,
	) {
		parent::__construct($name);
	}

	#[\Override]
	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		$this->queryString = 'RENAME TABLE '.Strings::quote($this->name).' TO '.Strings::quote($this->newName).';';
	}

}
