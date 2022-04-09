<?php

declare(strict_types=1);

namespace Reflexive\Query;

use Exception;

class Call extends Composed
{
	public function __construct()
	{
		parent::__construct('CALL');
		// $this->setColumns($columns);
		// $this->quoteNames = false;

		throw new Exception('NOT YET IMPLEMENTED');
	}
}
