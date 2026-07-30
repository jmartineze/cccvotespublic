<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->string('discord_user');
            $table->string('character_name');
            $table->string('country');
            $table->text('backstory');
            $table->enum('gender', ['Male', 'Female', 'Trans']);
            $table->enum('style', ['Anime', 'Realistic']);
            $table->timestamps();

            $table->unique(['contest_id', 'discord_user', 'gender']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
