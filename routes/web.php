<?php

use App\Enums\RoleCode;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentMentorAssignmentController;
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
