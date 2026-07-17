<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use App\Models\WebsiteAudit;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a demo-able dataset: one user per account_type, plus a handful
     * of campaigns/leads/audits owned by the sales user so the dashboard
     * and campaign pages have something to show right after `make seed`.
     *
     * Idempotent by email/ownership so re-running `db:seed` (without
     * `migrate:fresh`) never throws a duplicate-key error.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'account_type' => AccountType::Admin->value, 'password' => bcrypt('password')]
        );

        $sales = User::query()->firstOrCreate(
            ['email' => 'sales@example.com'],
            ['name' => 'Sales User', 'account_type' => AccountType::Sales->value, 'password' => bcrypt('password')]
        );

        User::query()->firstOrCreate(
            ['email' => 'viewer@example.com'],
            ['name' => 'Viewer User', 'account_type' => AccountType::Viewer->value, 'password' => bcrypt('password')]
        );

        if (Campaign::query()->where('user_id', $sales->id)->doesntExist()) {
            Campaign::factory()
                ->count(3)
                ->for($sales)
                ->has(
                    Lead::factory()
                        ->count(10)
                        ->has(WebsiteAudit::factory(), 'audits')
                )
                ->create();
        }
    }
}
