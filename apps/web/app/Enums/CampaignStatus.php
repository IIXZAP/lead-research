<?php

declare(strict_types=1);

namespace App\Enums;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Processing = 'processing';
    case PartiallyCompleted = 'partially_completed';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
