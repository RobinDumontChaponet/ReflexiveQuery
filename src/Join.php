<?php

declare(strict_types=1);

namespace Reflexive\Query;

enum Join: string {
	case inner = ' INNER JOIN ';
	case left = ' LEFT JOIN ';
	case right = ' RIGHT JOIN ';
	case full = ' FULL JOIN ';
}
