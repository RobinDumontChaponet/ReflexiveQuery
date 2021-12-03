<?php

namespace Reflexive\Core;

interface QueryMapperInterface
{
	public function prepare(\PDO $pdo): \PDOStatement;
}
