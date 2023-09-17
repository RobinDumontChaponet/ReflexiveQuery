<?php

declare(strict_types=1);

namespace Reflexive\Query;

enum ConstraintAction: string {
	case noAction = 'NO ACTION';
	case restrict = 'RESTRICT';
	case cascade = 'CASCADE';
	case setNull = 'SET NULL';
	case setDefault = 'SET DEFAULT';
}
