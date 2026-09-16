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
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_key')->unique();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->integer('points');
            $table->string('reason');
            $table->json('metadata')->nullable();
            $table->timestamp('awarded_at');
            $table->timestamps();
            $table->index(['student_id', 'academic_year_id', 'awarded_at'], 'xp_transactions_student_timeline_index');
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('current_days')->default(0);
            $table->unsignedInteger('longest_days')->default(0);
            $table->date('last_activity_on')->nullable();
            $table->unsignedSmallInteger('available_freezes')->default(0);
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id']);
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name');
            $table->string('name_marathi');
            $table->text('description')->nullable();
            $table->text('description_marathi')->nullable();
            $table->string('icon_path')->nullable();
            $table->json('criteria');
            $table->unsignedInteger('xp_bonus')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'code']);
        });

        Schema::create('student_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->timestamp('earned_at');
            $table->json('evidence')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'badge_id', 'academic_year_id'], 'student_badge_year_unique');
        });

        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('achievement_key', 100);
            $table->string('title');
            $table->string('title_marathi');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('achieved_at');
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id', 'achievement_key'], 'student_achievement_unique');
        });

        Schema::create('daily_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->date('goal_date');
            $table->unsignedSmallInteger('target_activities')->default(1);
            $table->unsignedSmallInteger('completed_activities')->default(0);
            $table->unsignedSmallInteger('target_minutes')->nullable();
            $table->unsignedSmallInteger('completed_minutes')->default(0);
            $table->unsignedInteger('target_xp')->nullable();
            $table->unsignedInteger('earned_xp')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'goal_date']);
            $table->index(['academic_year_id', 'goal_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_goals');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('student_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('streaks');
        Schema::dropIfExists('xp_transactions');
    }
};
