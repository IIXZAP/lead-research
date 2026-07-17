<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Qualified = 'qualified';
    case Contacted = 'contacted';
    case Interested = 'interested';
    case NotInterested = 'not_interested';
    case Invalid = 'invalid';
    case Duplicate = 'duplicate';
    case Converted = 'converted';
}
