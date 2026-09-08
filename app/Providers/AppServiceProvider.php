<?php

namespace App\Providers;

use App\Enums\RoleCode;
use App\Models\Mentor;
use App\Models\SchoolClass;
use App\Models\Student;
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
}
