<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contest_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('max_score');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('tiebreak_order')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();

            $table->unique(['contest_id', 'tiebreak_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contest_criteria');
    }
};
