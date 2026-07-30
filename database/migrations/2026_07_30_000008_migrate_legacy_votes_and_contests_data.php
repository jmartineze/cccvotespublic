<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $legacyContestIds = DB::table('contests')->whereNull('owner_id')->pluck('id');
        $legacyJudgeIds = DB::table('users')->where('role', 'judge')->whereNull('owner_id')->pluck('id');

        if ($legacyContestIds->isEmpty() && $legacyJudgeIds->isEmpty()) {
            return;
        }

        $tenantId = DB::table('users')->where('email', 'tenant@ccc.local')->value('id');

        if (! $tenantId) {
            $tenantId = DB::table('users')->insertGetId([
                'name' => 'Default Tenant',
                'email' => 'tenant@ccc.local',
                'password' => Hash::make('password'),
                'role' => 'tenant_admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($legacyContestIds as $contestId) {
            DB::table('contests')->where('id', $contestId)->update(['owner_id' => $tenantId]);

            $criteria = [
                ['name' => 'Composition', 'max_score' => 10, 'sort_order' => 1, 'tiebreak_order' => null],
                ['name' => 'Cultural Authenticity', 'max_score' => 20, 'sort_order' => 2, 'tiebreak_order' => 1],
                ['name' => 'Allure', 'max_score' => 10, 'sort_order' => 3, 'tiebreak_order' => null],
                ['name' => 'Backstory', 'max_score' => 10, 'sort_order' => 4, 'tiebreak_order' => null],
            ];

            $criterionIds = [];
            foreach ($criteria as $criterion) {
                $criterionIds[$criterion['name']] = DB::table('contest_criteria')->insertGetId([
                    'contest_id' => $contestId,
                    'name' => $criterion['name'],
                    'max_score' => $criterion['max_score'],
                    'sort_order' => $criterion['sort_order'],
                    'tiebreak_order' => $criterion['tiebreak_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $submissionIds = DB::table('submissions')->where('contest_id', $contestId)->pluck('id');

            $votes = DB::table('votes')->whereIn('submission_id', $submissionIds)->get();

            $scoreMap = [
                'composition_score' => 'Composition',
                'cultural_score' => 'Cultural Authenticity',
                'allure_score' => 'Allure',
                'backstory_score' => 'Backstory',
            ];

            foreach ($votes as $vote) {
                foreach ($scoreMap as $column => $criterionName) {
                    DB::table('vote_scores')->insert([
                        'vote_id' => $vote->id,
                        'contest_criterion_id' => $criterionIds[$criterionName],
                        'score' => $vote->$column,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        foreach ($legacyJudgeIds as $judgeId) {
            $judge = DB::table('users')->where('id', $judgeId)->first();
            $base = Str::slug(Str::before($judge->email ?? $judge->name, '@')) ?: 'judge';
            $username = $base;
            $suffix = 1;

            while (DB::table('users')->where('owner_id', $tenantId)->where('username', $username)->exists()) {
                $username = $base.$suffix;
                $suffix++;
            }

            DB::table('users')->where('id', $judgeId)->update([
                'owner_id' => $tenantId,
                'username' => $username,
                'email' => null,
            ]);
        }
    }

    public function down(): void
    {
        // Data migration: not reversible in a meaningful way.
    }
};
