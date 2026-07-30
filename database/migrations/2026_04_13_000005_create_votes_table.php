<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('composition_score');  // 0-10
            $table->unsignedTinyInteger('cultural_score');     // 0-20
            $table->unsignedTinyInteger('allure_score');       // 0-10
            $table->unsignedTinyInteger('backstory_score');    // 0-10
            $table->unsignedTinyInteger('total_score')->default(0); // 0-50
            $table->timestamps();

            $table->unique(['user_id', 'submission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
