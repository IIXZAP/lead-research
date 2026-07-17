<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\AccountType;
use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        // Everyone can view the campaign list; the query itself is scoped
        // per-role in the controller (admin sees all, sales sees own,
        // viewer sees only campaigns they've been granted access to).
        return true;
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return match ($user->account_type) {
            AccountType::Admin => true,
            AccountType::Sales => $campaign->user_id === $user->id,
            AccountType::Viewer => true, // scoped to granted campaigns at the query level
        };
    }

    public function create(User $user): bool
    {
        return in_array($user->account_type, [AccountType::Admin, AccountType::Sales], strict: true);
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return match ($user->account_type) {
            AccountType::Admin => true,
            AccountType::Sales => $campaign->user_id === $user->id,
            AccountType::Viewer => false,
        };
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin();
    }

    public function startJob(User $user, Campaign $campaign): bool
    {
        return match ($user->account_type) {
            AccountType::Admin => true,
            AccountType::Sales => $campaign->user_id === $user->id,
            AccountType::Viewer => false,
        };
    }

    public function cancelJob(User $user, Campaign $campaign): bool
    {
        return $this->startJob($user, $campaign);
    }

    public function export(User $user, Campaign $campaign): bool
    {
        return match ($user->account_type) {
            AccountType::Admin => true,
            AccountType::Sales => $campaign->user_id === $user->id,
            AccountType::Viewer => false,
        };
    }
}
