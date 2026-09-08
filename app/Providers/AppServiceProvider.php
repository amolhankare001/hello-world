<?php

namespace App\Providers;

use App\Enums\RoleCode;
use App\Models\Activity;
use App\Models\Game;
use App\Models\GameQuestion;
use App\Models\GameSession;
use App\Models\Mentor;
use App\Models\PracticeAttempt;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Simulation;
use App\Models\SimulationChallenge;
use App\Models\SimulationSession;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Test;
use App\Models\TestAttempt;
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
        Route::bind('question', fn (string $value): Model => $this->contentQuestion($value));
        Route::bind('practice_attempt', fn (string $value): Model => $this->studentPracticeAttempt($value));
        Route::bind('game', fn (string $value): Model => Game::query()->where('code', $value)->firstOrFail());
        Route::bind('game_session', fn (string $value): Model => $this->studentGameSession($value));
        Route::bind('game_question', fn (string $value): Model => $this->gameQuestion($value));
        Route::bind('simulation', fn (string $value): Model => Simulation::query()
            ->where('code', $value)
            ->firstOrFail());
        Route::bind('simulation_session', fn (string $value): Model => $this->studentSimulationSession($value));
        Route::bind('simulation_challenge', fn (string $value): Model => $this->simulationChallenge($value));
        Route::bind('test', fn (string $value): Model => $this->contentTest($value));
        Route::bind('student_test_attempt', fn (string $value): Model => $this->studentTestAttempt($value));
        Route::bind('test_attempt', fn (string $value): Model => $this->assessmentAttempt($value));
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

    private function contentQuestion(string $value): Model
    {
        $query = Question::query();
        $activity = request()->route('activity');

        if ($activity instanceof Activity) {
            $query->whereBelongsTo($activity);
        }

        return $query->findOrFail($value);
    }

    private function studentPracticeAttempt(string $value): Model
    {
        /** @var User|null $user */
        $user = request()->user();
        $studentId = $user?->student()->value('id');

        return PracticeAttempt::query()
            ->where('student_id', $studentId ?? 0)
            ->where('attempt_key', $value)
            ->firstOrFail();
    }

    private function contentTest(string $value): Model
    {
        $query = Test::query();
        $this->scopeContentQuery($query);

        return $query->findOrFail($value);
    }

    private function studentGameSession(string $value): Model
    {
        /** @var User|null $user */
        $user = request()->user();
        $studentId = $user?->student()->value('id');

        return GameSession::query()
            ->where('student_id', $studentId ?? 0)
            ->where('session_key', $value)
            ->firstOrFail();
    }

    private function gameQuestion(string $value): Model
    {
        $query = GameQuestion::query();
        $session = request()->route('game_session');

        if ($session instanceof GameSession) {
            $query->whereBelongsTo($session, 'session');
        }

        return $query->findOrFail($value);
    }

    private function studentSimulationSession(string $value): Model
    {
        /** @var User|null $user */
        $user = request()->user();
        $studentId = $user?->student()->value('id');

        return SimulationSession::query()
            ->where('student_id', $studentId ?? 0)
            ->where('session_key', $value)
            ->firstOrFail();
    }

    private function simulationChallenge(string $value): Model
    {
        $query = SimulationChallenge::query();
        $session = request()->route('simulation_session');

        if ($session instanceof SimulationSession) {
            $query->whereBelongsTo($session, 'session');
        }

        return $query->findOrFail($value);
    }

    private function studentTestAttempt(string $value): Model
    {
        /** @var User|null $user */
        $user = request()->user();
        $studentId = $user?->student()->value('id');

        return TestAttempt::query()
            ->where('student_id', $studentId ?? 0)
            ->where('attempt_key', $value)
            ->firstOrFail();
    }

    private function assessmentAttempt(string $value): Model
    {
        /** @var User|null $user */
        $user = request()->user();
        $query = TestAttempt::query()->where('attempt_key', $value);
        $test = request()->route('test');

        if ($test instanceof Test) {
            $query->whereBelongsTo($test);
        }

        if ($user !== null && ! $user->hasRole(RoleCode::SuperAdmin)) {
            $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery
                ->where('school_id', $user->school_id));
        }

        return $query->firstOrFail();
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
