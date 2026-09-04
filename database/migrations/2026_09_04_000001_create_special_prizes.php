<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Non-scoring, toggle-style side awards ("best image", "made me laugh"…).
        Schema::create('special_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // A judge may check any number of submissions for any number of prizes.
        Schema::create('special_prize_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_prize_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['special_prize_id', 'user_id', 'submission_id'], 'sp_vote_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_prize_votes');
        Schema::dropIfExists('special_prizes');
    }
};
