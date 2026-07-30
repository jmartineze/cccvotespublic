<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'tenant_admin', 'judge'])->default('judge')->after('email');
            $table->foreignId('owner_id')->nullable()->after('role')->constrained('users')->restrictOnDelete();
            $table->string('username')->nullable()->after('owner_id');
            $table->unique(['owner_id', 'username']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['owner_id', 'username']);
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn(['role', 'username']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
