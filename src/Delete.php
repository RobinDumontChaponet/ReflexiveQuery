<?php

declare(strict_types=1);

namespace Reflexive\Query;

class Delete extends Composed
{
	protected int $index = 0;

	public function __construct()
	{
		parent::__construct('DELETE');
	}

	protected function bake(): void
	{
		if(!empty($this->queryString))
			return;

		$this->parameters = [];
		$this->index = 0;

		$this->queryString = $this->command. (empty($this->commandEnd) ? ' ' : '');
		$this->queryString.= $this->getFromString();
		$this->queryString.= $this->getWhereString();
	}
}
