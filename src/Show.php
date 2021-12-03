<?php

declare(strict_types=1);

namespace Reflexive\Query;

class Show extends Composed
{
	public function __construct(?array $columns = [])
	{
		parent::__construct('SHOW');
		$this->setColumns($columns);
		$this->quoteColumns = false;
	}
}
