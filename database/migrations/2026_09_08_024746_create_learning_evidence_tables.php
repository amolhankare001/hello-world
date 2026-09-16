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
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('attempt_key')->unique();
            $table->foreignId('test_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('attempt_number')->default(1);
            $table->string('status', 30)->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->decimal('score', 10, 2)->nullable();
            $table->decimal('max_score', 10, 2)->nullable();
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('diagnosis')->nullable();
            $table->timestamps();
            $table->unique(['test_id', 'student_id', 'attempt_number']);
            $table->index(['student_id', 'academic_year_id', 'status'], 'test_attempts_student_status_index');
        });

        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->foreignId('question_option_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('error_type_id')->nullable()->constrained()->nullOnDelete();
            $table->json('answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('score', 8, 2)->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['test_attempt_id', 'question_id']);
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 80)->unique();
            $table->string('engine_key', 80);
            $table->string('title');
            $table->string('title_marathi');
            $table->text('description')->nullable();
            $table->text('description_marathi')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->json('configuration')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['engine_key', 'status']);
        });

        Schema::create('game_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level');
            $table->string('name');
            $table->string('name_marathi')->nullable();
            $table->unsignedSmallInteger('difficulty')->default(1);
            $table->json('configuration');
            $table->unsignedInteger('target_score')->nullable();
            $table->unsignedInteger('time_limit_seconds')->nullable();
            $table->timestamps();
            $table->unique(['game_id', 'level']);
        });

        Schema::create('game_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight', 5, 2)->default(1);
            $table->timestamps();
            $table->unique(['game_id', 'skill_id']);
        });

        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_key')->unique();
            $table->foreignId('game_id')->constrained()->restrictOnDelete();
            $table->foreignId('game_level_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('in_progress');
            $table->unsignedSmallInteger('difficulty')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('server_state')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'academic_year_id', 'status'], 'game_sessions_student_status_index');
        });

        Schema::create('game_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('max_score')->nullable();
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('incorrect_count')->default(0);
            $table->decimal('accuracy', 5, 2)->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->json('result_payload')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 80)->unique();
            $table->string('engine_key', 80);
            $table->string('title');
            $table->string('title_marathi');
            $table->text('description')->nullable();
            $table->text('description_marathi')->nullable();
            $table->json('configuration')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['engine_key', 'status']);
        });

        Schema::create('simulation_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight', 5, 2)->default(1);
            $table->timestamps();
            $table->unique(['simulation_id', 'skill_id']);
        });

        Schema::create('simulation_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_key')->unique();
            $table->foreignId('simulation_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('in_progress');
            $table->unsignedSmallInteger('difficulty')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->decimal('score', 10, 2)->nullable();
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->json('state')->nullable();
            $table->json('learning_events')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'academic_year_id', 'status'], 'simulation_sessions_student_status_index');
        });

        Schema::create('practice_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('question_count')->default(10);
            $table->boolean('randomize_questions')->default(true);
            $table->boolean('show_feedback_immediately')->default(true);
            $table->json('configuration')->nullable();
            $table->timestamps();
        });

        Schema::create('practice_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('attempt_key')->unique();
            $table->foreignId('practice_activity_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('in_progress');
            $table->unsignedSmallInteger('difficulty')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('incorrect_count')->default(0);
            $table->decimal('score', 10, 2)->nullable();
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('answers')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'academic_year_id', 'status'], 'practice_attempts_student_status_index');
        });

        Schema::create('student_skill_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('current_level')->default(1);
            $table->decimal('mastery_score', 5, 2)->default(0);
            $table->unsignedInteger('total_attempts')->default(0);
            $table->unsignedInteger('correct_attempts')->default(0);
            $table->decimal('accuracy', 5, 2)->default(0);
            $table->decimal('best_score', 10, 2)->default(0);
            $table->decimal('average_score', 10, 2)->default(0);
            $table->unsignedInteger('practice_count')->default(0);
            $table->unsignedInteger('game_count')->default(0);
            $table->unsignedInteger('simulation_count')->default(0);
            $table->decimal('pre_test_score', 10, 2)->nullable();
            $table->decimal('post_test_score', 10, 2)->nullable();
            $table->decimal('improvement', 10, 2)->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->string('status', 30)->default('not_started');
            $table->timestamps();
            $table->unique(['student_id', 'skill_id', 'academic_year_id'], 'student_skill_progress_unique');
            $table->index(['student_id', 'academic_year_id', 'status'], 'student_progress_status_index');
        });

        Schema::create('student_skill_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_key')->unique();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('error_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type', 30);
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('score', 10, 2)->nullable();
            $table->decimal('max_score', 10, 2)->nullable();
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedSmallInteger('difficulty')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->integer('xp_awarded')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['student_id', 'skill_id', 'occurred_at'], 'student_skill_events_timeline_index');
            $table->index(['student_id', 'academic_year_id', 'activity_type'], 'student_skill_events_type_index');
            $table->index(['source_type', 'source_id'], 'student_skill_events_source_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_skill_events');
        Schema::dropIfExists('student_skill_progress');
        Schema::dropIfExists('practice_attempts');
        Schema::dropIfExists('practice_activities');
        Schema::dropIfExists('simulation_sessions');
        Schema::dropIfExists('simulation_skills');
        Schema::dropIfExists('simulations');
        Schema::dropIfExists('game_results');
        Schema::dropIfExists('game_sessions');
        Schema::dropIfExists('game_skills');
        Schema::dropIfExists('game_levels');
        Schema::dropIfExists('games');
        Schema::dropIfExists('test_answers');
        Schema::dropIfExists('test_attempts');
    }
};
