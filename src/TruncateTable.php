<?php

declare(strict_types=1);

namespace Reflexive\Query;

use \Reflexive\Core\Strings;

class TruncateTable extends TableQuery
{
	#[\Override]
	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		$this->queryString = 'TRUNCATE TABLE '.Strings::quote($this->name).';';
	}

}
