<?php

namespace Database\Seeders;

use App\Models\Contest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@ccc.local'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'role' => 'super_admin']
        );

        $tenant = User::firstOrCreate(
            ['email' => 'tenant1@ccc.local'],
            ['name' => 'Tenant One', 'password' => Hash::make('password'), 'role' => 'tenant_admin']
        );

        foreach (['alpha' => 'Judge Alpha', 'beta' => 'Judge Beta', 'gamma' => 'Judge Gamma', 'delta' => 'Co-admin Delta'] as $username => $name) {
            User::firstOrCreate(
                ['username' => $username],
                ['name' => $name, 'password' => Hash::make('password'), 'role' => 'judge', 'owner_id' => $tenant->id]
            );
        }

        // A second tenant. Alpha is a member of both; Delta co-admins both.
        $tenant2 = User::firstOrCreate(
            ['email' => 'tenant2@ccc.local'],
            ['name' => 'Tenant Two', 'password' => Hash::make('password'), 'role' => 'tenant_admin']
        );

        $alpha = User::where('username', 'alpha')->first();
        $alpha?->memberships()->firstOrCreate(['tenant_id' => $tenant2->id], ['role' => 'judge']);

        $delta = User::where('username', 'delta')->first();
        $delta?->memberships()->updateOrCreate(['tenant_id' => $tenant->id], ['role' => 'co_admin']);
        $delta?->memberships()->updateOrCreate(['tenant_id' => $tenant2->id], ['role' => 'co_admin']);

        foreach ([
            [$tenant, 'Culture Cuties Contest S1'],
            [$tenant2, 'Tenant Two · Winter Cup'],
        ] as [$owner, $name]) {
            $contest = Contest::firstOrCreate(
                ['owner_id' => $owner->id, 'name' => $name],
                ['status' => 'draft', 'contest_type' => 'image']
            );

            if ($contest->wasRecentlyCreated) {
                $contest->criteria()->createMany([
                    ['name' => 'Composition', 'description' => 'Visual balance, pose & framing', 'max_score' => 10, 'sort_order' => 1, 'tiebreak_order' => null],
                    ['name' => 'Cultural Authenticity', 'description' => 'Accuracy & depth of cultural representation', 'max_score' => 20, 'sort_order' => 2, 'tiebreak_order' => 1],
                    ['name' => 'Allure', 'description' => 'Visual appeal & overall impression', 'max_score' => 10, 'sort_order' => 3, 'tiebreak_order' => null],
                    ['name' => 'Backstory', 'description' => 'Creativity & quality of the written backstory', 'max_score' => 10, 'sort_order' => 4, 'tiebreak_order' => null],
                ]);

                $contest->specialPrizes()->createMany([
                    ['name' => 'Best image', 'description' => 'Purely on visual craft', 'sort_order' => 0],
                    ['name' => 'Made me laugh', 'description' => 'The funniest entry', 'sort_order' => 1],
                    ['name' => 'Slowest burn', 'description' => 'Most delicious tension', 'sort_order' => 2],
                ]);
            }
        }
    }
}
