<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Co-admin becomes a per-tenant capability: a judge can be co-admin of
        // several tenants at once.
        Schema::table('tenant_memberships', function (Blueprint $table) {
            $table->enum('role', ['judge', 'co_admin'])->default('judge')->after('user_id');
        });

        // Move every existing co_admin onto a membership row and downgrade the
        // account role to 'judge'.
        foreach (DB::table('users')->where('role', 'co_admin')->get(['id', 'owner_id']) as $coAdmin) {
            if ($coAdmin->owner_id) {
                DB::table('tenant_memberships')->updateOrInsert(
                    ['tenant_id' => $coAdmin->owner_id, 'user_id' => $coAdmin->id],
                    ['role' => 'co_admin', 'updated_at' => now(), 'created_at' => now()],
                );
            }
            DB::table('users')->where('id', $coAdmin->id)->update(['role' => 'judge']);
        }

        // Shrink the users.role enum back — co_admin no longer lives there.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['super_admin', 'tenant_admin', 'judge'])->default('judge')->change();
            });
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'tenant_admin', 'judge') NOT NULL DEFAULT 'judge'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['super_admin', 'tenant_admin', 'co_admin', 'judge'])->default('judge')->change();
            });
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'tenant_admin', 'co_admin', 'judge') NOT NULL DEFAULT 'judge'");
        }

        // Restore users.role from the membership rows (first co_admin membership wins).
        foreach (DB::table('tenant_memberships')->where('role', 'co_admin')->get(['user_id']) as $row) {
            DB::table('users')->where('id', $row->user_id)->where('role', 'judge')->update(['role' => 'co_admin']);
        }

        Schema::table('tenant_memberships', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
