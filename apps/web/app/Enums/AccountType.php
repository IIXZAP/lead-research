<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountType: string
{
    case Admin = 'admin';
    case Sales = 'sales';
    case Viewer = 'viewer';
}
