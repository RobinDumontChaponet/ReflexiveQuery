<?php

declare(strict_types=1);

namespace Reflexive\Core;

class ShowQuery extends ComposedQuery
{
	public function __construct(?array $columns = [])
	{
		parent::__construct('SHOW');
		$this->setColumns($columns);
		$this->quoteColumns = false;
	}
}
