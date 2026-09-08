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
        Schema::create('simulation_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('prompt');
            $table->string('prompt_marathi');
            $table->json('interaction');
            $table->json('expected_state');
            $table->unsignedInteger('max_score')->default(100);
            $table->timestamps();
            $table->unique(['simulation_session_id', 'sequence']);
            $table->index(['skill_id', 'sequence']);
        });

        Schema::create('simulation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('simulation_challenge_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('event_type', 40)->default('submission');
            $table->json('payload');
            $table->boolean('is_success');
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('response_ms');
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->unique(['simulation_challenge_id', 'attempt_number']);
            $table->index(['simulation_session_id', 'occurred_at']);
        });

        Schema::create('simulation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('max_score')->default(0);
            $table->unsignedSmallInteger('successful_count')->default(0);
            $table->unsignedSmallInteger('unsuccessful_count')->default(0);
            $table->decimal('accuracy', 5, 2)->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->json('result_payload')->nullable();
            $table->timestamp('validated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulation_results');
        Schema::dropIfExists('simulation_events');
        Schema::dropIfExists('simulation_challenges');
    }
};
