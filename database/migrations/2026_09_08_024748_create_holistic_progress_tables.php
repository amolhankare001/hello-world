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
        Schema::create('holistic_domains', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->string('name_marathi');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('holistic_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holistic_domain_id')->constrained()->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->string('name_marathi');
            $table->text('description')->nullable();
            $table->json('rating_scale')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['holistic_domain_id', 'code']);
        });

        Schema::create('mentor_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('holistic_domain_id')->nullable()->constrained()->nullOnDelete();
            $table->date('observed_on');
            $table->string('category', 60)->nullable();
            $table->text('observation');
            $table->string('visibility', 30)->default('mentor');
            $table->timestamps();
            $table->index(['student_id', 'academic_year_id', 'observed_on'], 'mentor_observations_timeline_index');
        });

        Schema::create('holistic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('holistic_indicator_id')->constrained()->restrictOnDelete();
            $table->decimal('rating', 5, 2);
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['student_id', 'academic_year_id', 'recorded_at'], 'holistic_records_timeline_index');
        });

        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('reason');
            $table->text('plan');
            $table->string('status', 30)->default('planned');
            $table->date('starts_on')->nullable();
            $table->date('target_completion_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->text('outcome')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'academic_year_id', 'status'], 'interventions_student_status_index');
        });

        Schema::create('intervention_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intervention_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('instructions')->nullable();
            $table->date('assigned_on');
            $table->date('completed_on')->nullable();
            $table->text('mentor_notes')->nullable();
            $table->timestamps();
            $table->index(['intervention_id', 'completed_on']);
        });

        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('disk', 40)->default('s3');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->boolean('is_private')->default(true);
            $table->date('occurred_on')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'academic_year_id', 'type'], 'portfolio_items_student_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
        Schema::dropIfExists('intervention_activities');
        Schema::dropIfExists('interventions');
        Schema::dropIfExists('holistic_records');
        Schema::dropIfExists('mentor_observations');
        Schema::dropIfExists('holistic_indicators');
        Schema::dropIfExists('holistic_domains');
    }
};
