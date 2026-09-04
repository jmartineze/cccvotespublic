<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. A judge can now belong to many tenants.
        Schema::create('tenant_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
        });

        // 2. Backfill: every existing judge's home tenant becomes a membership.
        $judges = DB::table('users')->where('role', 'judge')->whereNotNull('owner_id')->get(['id', 'owner_id']);
        foreach ($judges as $judge) {
            DB::table('tenant_memberships')->insertOrIgnore([
                'tenant_id' => $judge->owner_id,
                'user_id' => $judge->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Usernames become globally unique. Resolve any cross-tenant
        //    collisions first (keep the oldest, suffix the rest).
        $dupes = DB::table('users')
            ->select('username')
            ->whereNotNull('username')
            ->groupBy('username')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('username');

        foreach ($dupes as $username) {
            $rows = DB::table('users')->where('username', $username)->orderBy('id')->pluck('id');
            foreach ($rows->slice(1) as $i => $id) {
                DB::table('users')->where('id', $id)->update(['username' => $username.'_'.($i + 2)]);
            }
        }

        // The composite (owner_id, username) index also backs the owner_id
        // foreign key, so give the FK its own index before dropping it.
        Schema::table('users', function (Blueprint $table) {
            $table->index('owner_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_owner_id_username_unique');
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->unique(['owner_id', 'username']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_owner_id_index');
        });

        Schema::dropIfExists('tenant_memberships');
    }
};
