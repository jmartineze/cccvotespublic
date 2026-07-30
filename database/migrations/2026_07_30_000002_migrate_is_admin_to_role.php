<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('is_admin', true)->update(['role' => 'super_admin']);
        DB::table('users')->where('is_admin', false)->update(['role' => 'judge']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
        });

        DB::table('users')->where('role', 'super_admin')->update(['is_admin' => true]);
        DB::table('users')->whereIn('role', ['tenant_admin', 'judge'])->update(['is_admin' => false]);
    }
};
