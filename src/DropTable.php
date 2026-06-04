<?php

declare(strict_types=1);

namespace Reflexive\Query;

use \Reflexive\Core\Strings;

class DropTable extends TableQuery
{
	public function __construct(
		string $name,
		protected bool $ifExists = false,
	) {
		parent::__construct($name);
	}

	#[\Override]
	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		$this->queryString = 'DROP TABLE ';
		$this->queryString.= $this->ifExists ? 'IF EXISTS ' : '';
		$this->queryString.= Strings::quote($this->name).';';
	}

}
