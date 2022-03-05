<?php

declare(strict_types=1);

namespace Reflexive\Query;

class Simple
{
	protected $queryString;

	function __construct(string $queryString = '')
	{
		$this->queryString = $queryString;
	}

	public function prepare(\PDO $pdo): \PDOStatement
	{
		$statement = $pdo->prepare($this->queryString);
		$statement->setFetchMode(\PDO::FETCH_OBJ);

		return $statement;
	}

	public function __toString(): string
	{
		return $this->queryString;
	}

	public static function read(\PDOStatement $statement, string $key): mixed
	{
		// Get row data
		if($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
			return $row[$key];
		}

		return null;
	}

	public static function format(?\PDOStatement $statement): string
	{
		if(null === $statement || $statement->rowCount() <= 0)
			return 'No result';

		$count = $statement->columnCount();
		$str = '';

		// Get column headers
		$str.= '<table><thead><tr>';
		for ($i = 0; $i < $count; $i++){
			$meta = $statement->getColumnMeta($i)["name"];
			$str.= '<th>' . $meta . '</th>';
		}
		$str.= '</tr></thead><tbody>';

		// Get row data
		while($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
			$str.= '<tr>';
			for ($i = 0; $i < $count; $i++){
				$meta = $statement->getColumnMeta($i)["name"];
				$str.= '<td>' . $row[$meta] . '</td>';
			}
			$str.= '</tr>';
		}

		$str.= '</tbody></table>';

		return $str;
	}
}
