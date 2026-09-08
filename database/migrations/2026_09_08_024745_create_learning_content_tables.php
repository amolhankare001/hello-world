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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->string('name_marathi');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'code']);
            $table->index(['school_id', 'is_active', 'sort_order']);
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->string('name_marathi');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['subject_id', 'code']);
            $table->index(['subject_id', 'is_active', 'sort_order']);
        });

        Schema::create('skill_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level');
            $table->string('name');
            $table->string('name_marathi');
            $table->text('learning_objective')->nullable();
            $table->decimal('mastery_threshold', 5, 2)->default(80);
            $table->json('configuration')->nullable();
            $table->timestamps();
            $table->unique(['skill_id', 'level']);
        });

        Schema::create('error_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->string('name_marathi')->nullable();
            $table->text('description')->nullable();
            $table->json('remediation')->nullable();
            $table->timestamps();
            $table->unique(['skill_id', 'code']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('skill_level_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 80)->unique();
            $table->string('type', 30);
            $table->string('title');
            $table->string('title_marathi');
            $table->text('instructions')->nullable();
            $table->text('instructions_marathi')->nullable();
            $table->json('content')->nullable();
            $table->unsignedSmallInteger('difficulty')->default(1);
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('max_score')->default(100);
            $table->string('status', 30)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['skill_id', 'type', 'status']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('error_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->text('prompt');
            $table->text('prompt_marathi')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('media_path')->nullable();
            $table->json('correct_answer');
            $table->text('explanation')->nullable();
            $table->text('explanation_marathi')->nullable();
            $table->unsignedSmallInteger('difficulty')->default(1);
            $table->decimal('marks', 8, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['skill_id', 'difficulty', 'is_active']);
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('label');
            $table->text('label_marathi')->nullable();
            $table->string('media_path')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['question_id', 'sort_order']);
        });

        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 80)->unique();
            $table->string('type', 30);
            $table->string('title');
            $table->string('title_marathi');
            $table->text('instructions')->nullable();
            $table->text('instructions_marathi')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedSmallInteger('max_attempts')->default(1);
            $table->decimal('passing_score', 5, 2)->nullable();
            $table->boolean('shuffle_questions')->default(false);
            $table->string('status', 30)->default('draft');
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['school_id', 'academic_year_id', 'type', 'status'], 'tests_scope_status_index');
        });

        Schema::create('test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->decimal('marks', 8, 2)->nullable();
            $table->timestamps();
            $table->unique(['test_id', 'question_id']);
            $table->index(['test_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_questions');
        Schema::dropIfExists('tests');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('error_types');
        Schema::dropIfExists('skill_levels');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('subjects');
    }
};
