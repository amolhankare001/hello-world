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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('name_marathi')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('timezone', 50)->default('Asia/Kolkata');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->foreignId('school_id')->nullable()->after('role_id')->constrained()->nullOnDelete();
            $table->string('preferred_locale', 10)->default('mr')->after('password');
            $table->boolean('is_active')->default(true)->after('preferred_locale');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->index(['school_id', 'role_id', 'is_active']);
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false);
            $table->string('status', 30)->default('planned');
            $table->timestamps();
            $table->unique(['school_id', 'name']);
            $table->index(['school_id', 'is_current']);
        });

        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('name_marathi')->nullable();
            $table->unsignedSmallInteger('grade_level');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'grade_level']);
        });

        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30);
            $table->string('name_marathi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_class_id', 'name']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('student_number', 50);
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('joined_on')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone', 30)->nullable();
            $table->json('accessibility_preferences')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'student_number']);
            $table->index(['school_id', 'deleted_at']);
        });

        Schema::create('mentors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('employee_number', 50)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('qualifications')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'employee_number']);
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('division_id')->constrained()->restrictOnDelete();
            $table->string('roll_number', 30)->nullable();
            $table->date('enrolled_on');
            $table->date('ended_on')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id']);
            $table->index(['division_id', 'academic_year_id', 'status']);
        });

        Schema::create('student_mentor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->date('assigned_on');
            $table->date('ended_on')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
            $table->unique(['student_id', 'mentor_id', 'academic_year_id', 'assigned_on'], 'student_mentor_assignment_unique');
            $table->index(['mentor_id', 'academic_year_id', 'ended_on'], 'mentor_assignment_scope_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_mentor_assignments');
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('mentors');
        Schema::dropIfExists('students');
        Schema::dropIfExists('divisions');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('academic_years');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'role_id', 'is_active']);
            $table->dropConstrainedForeignId('school_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['preferred_locale', 'is_active', 'last_login_at']);
        });

        Schema::dropIfExists('schools');
        Schema::dropIfExists('roles');
    }
};
