<?php

use App\Enums\RoleCode;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\PracticeActivityController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SkillLevelController;
use App\Http\Controllers\StudentAssessmentController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentGameController;
use App\Http\Controllers\StudentMentorAssignmentController;
use App\Http\Controllers\StudentPracticeController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/platform/dashboard', DashboardController::class)
        ->middleware('role:'.RoleCode::SuperAdmin->value)
        ->name('super-admin.dashboard');
    Route::get('/school/dashboard', DashboardController::class)
        ->middleware('role:'.RoleCode::SchoolAdmin->value)
        ->name('school-admin.dashboard');
    Route::get('/mentor/dashboard', DashboardController::class)
        ->middleware('role:'.RoleCode::Mentor->value)
        ->name('mentor.dashboard');
    Route::get('/student/dashboard', DashboardController::class)
        ->middleware('role:'.RoleCode::Student->value)
        ->name('student.dashboard');

    Route::resource('schools', SchoolController::class)
        ->except('destroy')
        ->middleware('role:'.RoleCode::SuperAdmin->value);

    Route::middleware('role:'.RoleCode::SuperAdmin->value)->group(function (): void {
        Route::resource('subjects', SubjectController::class)->except('destroy');
        Route::post('subjects/{subject}/skills', [SkillController::class, 'store'])
            ->name('subjects.skills.store');
        Route::get('subjects/{subject}/skills/{skill}/edit', [SkillController::class, 'edit'])
            ->scopeBindings()
            ->name('subjects.skills.edit');
        Route::put('subjects/{subject}/skills/{skill}', [SkillController::class, 'update'])
            ->scopeBindings()
            ->name('subjects.skills.update');
        Route::post('subjects/{subject}/skills/{skill}/levels', [SkillLevelController::class, 'store'])
            ->scopeBindings()
            ->name('subjects.skills.levels.store');
        Route::put(
            'subjects/{subject}/skills/{skill}/levels/{skill_level}',
            [SkillLevelController::class, 'update'],
        )
            ->scopeBindings()
            ->name('subjects.skills.levels.update');
    });

    Route::resource('activities', ActivityController::class)
        ->except('destroy')
        ->middleware('role:'.implode(',', [
            RoleCode::SuperAdmin->value,
            RoleCode::SchoolAdmin->value,
            RoleCode::Mentor->value,
        ]));
    Route::middleware('role:'.implode(',', [
        RoleCode::SuperAdmin->value,
        RoleCode::SchoolAdmin->value,
        RoleCode::Mentor->value,
    ]))->group(function (): void {
        Route::resource('tests', AssessmentController::class)->except('destroy');
        Route::get('tests/{test}/attempts/{test_attempt}', [AssessmentController::class, 'attempt'])
            ->scopeBindings()
            ->name('tests.attempts.show');
        Route::put('activities/{activity}/practice', [PracticeActivityController::class, 'update'])
            ->name('activities.practice.update');
        Route::post('activities/{activity}/questions', [QuestionController::class, 'store'])
            ->name('activities.questions.store');
        Route::get('activities/{activity}/questions/{question}/edit', [QuestionController::class, 'edit'])
            ->scopeBindings()
            ->name('activities.questions.edit');
        Route::put('activities/{activity}/questions/{question}', [QuestionController::class, 'update'])
            ->scopeBindings()
            ->name('activities.questions.update');
    });

    Route::middleware('role:'.RoleCode::Student->value)->group(function (): void {
        Route::get('games', [StudentGameController::class, 'index'])->name('games.index');
        Route::post('games/{game}/start', [StudentGameController::class, 'start'])->name('games.start');
        Route::get('game-sessions/{game_session}', [StudentGameController::class, 'show'])
            ->name('games.sessions.show');
        Route::post(
            'game-sessions/{game_session}/questions/{game_question}',
            [StudentGameController::class, 'answer'],
        )->middleware('throttle:60,1')->name('games.sessions.answer');
        Route::post('game-sessions/{game_session}/finish', [StudentGameController::class, 'finish'])
            ->middleware('throttle:20,1')
            ->name('games.sessions.finish');
        Route::get('game-sessions/{game_session}/result', [StudentGameController::class, 'result'])
            ->name('games.sessions.result');
        Route::get('assessments', [StudentAssessmentController::class, 'index'])
            ->name('assessments.index');
        Route::post('assessments/{test}/start', [StudentAssessmentController::class, 'start'])
            ->name('assessments.start');
        Route::get('test-attempts/{student_test_attempt}', [StudentAssessmentController::class, 'show'])
            ->name('assessments.attempts.show');
        Route::post('test-attempts/{student_test_attempt}', [StudentAssessmentController::class, 'submit'])
            ->name('assessments.attempts.submit');
        Route::get(
            'test-attempts/{student_test_attempt}/result',
            [StudentAssessmentController::class, 'result'],
        )->name('assessments.attempts.result');
        Route::get('practice', [StudentPracticeController::class, 'index'])->name('practice.index');
        Route::post('practice/{activity}/start', [StudentPracticeController::class, 'start'])
            ->name('practice.start');
        Route::get('practice-attempts/{practice_attempt}', [StudentPracticeController::class, 'show'])
            ->name('practice.attempts.show');
        Route::post('practice-attempts/{practice_attempt}', [StudentPracticeController::class, 'submit'])
            ->name('practice.attempts.submit');
        Route::get('practice-attempts/{practice_attempt}/result', [StudentPracticeController::class, 'result'])
            ->name('practice.attempts.result');
    });

    Route::middleware('role:'.RoleCode::SchoolAdmin->value)->group(function (): void {
        Route::resource('school-classes', SchoolClassController::class)->except('destroy');
        Route::post('school-classes/{school_class}/divisions', [DivisionController::class, 'store'])
            ->name('school-classes.divisions.store');
        Route::put('school-classes/{school_class}/divisions/{division}', [DivisionController::class, 'update'])
            ->scopeBindings()
            ->name('school-classes.divisions.update');
        Route::resource('students', StudentController::class)->except('destroy');
        Route::post('students/{student}/mentor-assignment', [StudentMentorAssignmentController::class, 'store'])
            ->name('students.mentor-assignment.store');
        Route::resource('mentors', MentorController::class)->except('destroy');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
