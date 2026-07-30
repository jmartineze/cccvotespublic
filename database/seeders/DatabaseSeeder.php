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

        foreach ([
            ['name' => 'Judge Alpha', 'username' => 'alpha'],
            ['name' => 'Judge Beta',  'username' => 'beta'],
            ['name' => 'Judge Gamma', 'username' => 'gamma'],
        ] as $judge) {
            User::firstOrCreate(
                ['owner_id' => $tenant->id, 'username' => $judge['username']],
                ['name' => $judge['name'], 'password' => Hash::make('password'), 'role' => 'judge']
            );
        }

        $contest = Contest::firstOrCreate(
            ['owner_id' => $tenant->id, 'name' => 'Culture Cuties Contest S1'],
            ['status' => 'draft', 'contest_type' => 'image']
        );

        if ($contest->wasRecentlyCreated) {
            $contest->criteria()->createMany([
                ['name' => 'Composition', 'description' => 'Visual balance, pose & framing', 'max_score' => 10, 'sort_order' => 1, 'tiebreak_order' => null],
                ['name' => 'Cultural Authenticity', 'description' => 'Accuracy & depth of cultural representation', 'max_score' => 20, 'sort_order' => 2, 'tiebreak_order' => 1],
                ['name' => 'Allure', 'description' => 'Visual appeal & overall impression', 'max_score' => 10, 'sort_order' => 3, 'tiebreak_order' => null],
                ['name' => 'Backstory', 'description' => 'Creativity & quality of the written backstory', 'max_score' => 10, 'sort_order' => 4, 'tiebreak_order' => null],
            ]);
        }
    }
}
