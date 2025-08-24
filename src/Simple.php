<?php

declare(strict_types=1);

namespace Reflexive\Query;

use DomainException;

class Simple implements \Stringable
{
	/** count the number of call to prepare() */
	public static int $prepareCount = 0;

	protected ?string $appendString = '';

	function __construct(
		protected ?string $queryString = ''
	) {}

	/**
	 * return a PDOStatement using $pdo database connection
	 * @throws \TypeError if for whatever reason PDO->prepare do not return a PDOStatement
	 * @throws \DomainException if $queryString is empty
	 */
	public function prepare(\PDO $pdo): \PDOStatement
	{
		$string = $this->queryString .' '. $this->appendString;

		if(empty($string))
			throw new DomainException('Empty query string');

		$statement = $pdo->prepare($string, [
			// \PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL,
		]);

		if($statement === false) {
			throw new \TypeError('PDO->prepare did not return a PDOStatement');
		}

		static::$prepareCount++;
		$statement->setFetchMode(\PDO::FETCH_OBJ);

		return $statement;
	}

	public function __toString(): string
	{
		return $this->queryString ?? ' ' . $this->appendString ?? '';
	}

	/** fetch from $statement, returning data associated to $key or null */
	public static function read(\PDOStatement $statement, string $key): mixed
	{
		if(null === $statement->errorCode())
			$statement->execute();

		// Get row data
		if($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
			return $row[$key];
		}

		return null;
	}

	/** utility method, return string containing html table or "No result" after fetching from $statement */
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
				$str.= '<td>' . ($row[$meta] ?? '') . '</td>';
			}
			$str.= '</tr>';
		}

		$str.= '</tbody></table>';

		return $str;
	}

	public function append(string $string): static
	{
		$this->appendString .= $string;

		return $this;
	}
}
