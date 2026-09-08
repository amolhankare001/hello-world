<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('type', 40)->default('choice');
            $table->string('prompt');
            $table->string('prompt_marathi');
            $table->json('choices');
            $table->json('expected_answer');
            $table->unsignedSmallInteger('difficulty')->default(1);
            $table->unsignedInteger('max_score')->default(100);
            $table->unsignedInteger('response_time_limit_ms')->nullable();
            $table->timestamps();
            $table->unique(['game_session_id', 'sequence']);
            $table->index(['skill_id', 'difficulty']);
        });

        Schema::create('game_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_question_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('answer')->nullable();
            $table->boolean('is_correct');
            $table->unsignedInteger('response_ms');
            $table->unsignedInteger('score')->default(0);
            $table->timestamp('answered_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_answers');
        Schema::dropIfExists('game_questions');
    }
};
