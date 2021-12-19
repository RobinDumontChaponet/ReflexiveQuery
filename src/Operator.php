<?php

declare(strict_types=1);

namespace Reflexive\Query;

enum Operator: string {
	case AND = ' AND ';
	case OR = ' OR ';
}
