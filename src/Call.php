<?php

declare(strict_types=1);

namespace Reflexive\Query;

use Exception;

class Call extends Composed
{
	public function __construct(array|string|null $columns = [])
	{
		parent::__construct('CALL');
		// $this->setColumns($columns);
		// $this->quoteColumns = false;

		throw new Exception('NOT YET IMPLEMENTDE');
	}
}
