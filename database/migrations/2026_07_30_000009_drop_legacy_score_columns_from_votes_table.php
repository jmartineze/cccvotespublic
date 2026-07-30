<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropColumn(['composition_score', 'cultural_score', 'allure_score', 'backstory_score']);
        });
    }

    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->unsignedTinyInteger('composition_score')->default(0)->after('submission_id');
            $table->unsignedTinyInteger('cultural_score')->default(0)->after('composition_score');
            $table->unsignedTinyInteger('allure_score')->default(0)->after('cultural_score');
            $table->unsignedTinyInteger('backstory_score')->default(0)->after('allure_score');
        });
    }
};
