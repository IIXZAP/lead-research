<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\AccountType;
use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Lead $lead): bool
    {
        return match ($user->account_type) {
            AccountType::Admin => true,
            AccountType::Sales => $lead->campaign->user_id === $user->id,
            AccountType::Viewer => true, // scoped to granted campaigns at the query level
        };
    }

    public function updateStatus(User $user, Lead $lead): bool
    {
        return match ($user->account_type) {
            AccountType::Admin => true,
            AccountType::Sales => $lead->campaign->user_id === $user->id,
            AccountType::Viewer => false,
        };
    }

    public function addNote(User $user, Lead $lead): bool
    {
        return $this->updateStatus($user, $lead);
    }

    public function export(User $user, Lead $lead): bool
    {
        return $this->updateStatus($user, $lead);
    }
}
