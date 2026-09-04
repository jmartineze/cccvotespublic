<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the "co_admin" role to the enum. Raw SQL on MySQL/MariaDB; the
        // schema builder handles the SQLite table rebuild used in tests.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['super_admin', 'tenant_admin', 'co_admin', 'judge'])
                    ->default('judge')
                    ->change();
            });
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'tenant_admin', 'co_admin', 'judge') NOT NULL DEFAULT 'judge'");
        }

        Schema::table('users', function (Blueprint $table) {
            // tenant_admin / co_admin only: when true the user browses and votes
            // as a judge instead of seeing the admin panel.
            $table->boolean('judge_mode')->default(false)->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('judge_mode');
        });

        DB::table('users')->where('role', 'co_admin')->update(['role' => 'judge']);

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['super_admin', 'tenant_admin', 'judge'])
                    ->default('judge')
                    ->change();
            });
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'tenant_admin', 'judge') NOT NULL DEFAULT 'judge'");
        }
    }
};
