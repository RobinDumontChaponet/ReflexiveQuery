<?php

declare(strict_types=1);

namespace Reflexive\Query;

enum ColumnExtra: string {
	case autoIncrement = 'AUTO_INCREMENT';
	case onUpdateCurrent = 'ON UPDATE current_timestamp()';
	case serial = 'SERIAL DEFAULT VALUE';
}
