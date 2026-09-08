<?php

namespace App\Providers;

use App\Enums\RoleCode;
use App\Models\Activity;
use App\Models\Mentor;
use App\Models\SchoolClass;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Paginator::useBootstrapFive();

        Route::bind('student', fn (string $value): Model => $this->tenantModel(Student::query(), $value));
        Route::bind('mentor', fn (string $value): Model => $this->tenantModel(Mentor::query(), $value));
        Route::bind('school_class', fn (string $value): Model => $this->tenantModel(SchoolClass::query(), $value));
        Route::bind('subject', fn (string $value): Model => $this->contentSubject($value));
        Route::bind('skill', fn (string $value): Model => $this->contentSkill($value));
        Route::bind('skill_level', fn (string $value): Model => $this->contentSkillLevel($value));
        Route::bind('activity', fn (string $value): Model => $this->contentActivity($value));
    }

    private function tenantModel(Builder $query, string $value): Model
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user !== null && ! $user->hasRole(RoleCode::SuperAdmin)) {
            $query->where('school_id', $user->school_id);
        }

        return $query->findOrFail($value);
    }

    private function contentSubject(string $value): Model
    {
        $query = Subject::query();
        $this->scopeContentQuery($query);

        return $query->findOrFail($value);
    }

    private function contentSkill(string $value): Model
    {
        $query = Skill::query();
        $this->scopeContentQuery($query, 'subject');
        $subject = request()->route('subject');

        if ($subject instanceof Subject) {
            $query->whereBelongsTo($subject);
        }

        return $query->findOrFail($value);
    }

    private function contentSkillLevel(string $value): Model
    {
        $query = SkillLevel::query();
        $this->scopeContentQuery($query, 'skill.subject');
        $skill = request()->route('skill');

        if ($skill instanceof Skill) {
            $query->whereBelongsTo($skill);
        }

        return $query->findOrFail($value);
    }

    private function contentActivity(string $value): Model
    {
        $query = Activity::query();
        $this->scopeContentQuery($query);

        return $query->findOrFail($value);
    }

    private function scopeContentQuery(Builder $query, ?string $relationship = null): void
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user === null || $user->hasRole(RoleCode::SuperAdmin)) {
            return;
        }

        $scope = fn (Builder $builder): Builder => $builder
            ->whereNull('school_id')
            ->orWhere('school_id', $user->school_id);

        if ($relationship === null) {
            $query->where($scope);

            return;
        }

        $query->whereHas($relationship, $scope);
    }
}
