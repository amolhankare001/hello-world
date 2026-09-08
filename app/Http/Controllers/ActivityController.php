<?php

namespace App\Http\Controllers;

use App\Enums\RoleCode;
use App\Http\Requests\Content\StoreActivityRequest;
use App\Http\Requests\Content\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Activity::class);

        /** @var User $user */
        $user = request()->user();
        $activities = Activity::query()
            ->when(! $user->hasRole(RoleCode::SuperAdmin), fn ($query) => $query
                ->where(fn ($scope) => $scope
                    ->whereNull('school_id')
                    ->orWhere('school_id', $user->school_id)))
            ->with([
                'creator:id,name',
                'school:id,name,name_marathi',
                'skill:id,subject_id,name,name_marathi',
                'skill.subject:id,name,name_marathi',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('content.activities.index', compact('activities'));
    }

    public function create(): View
    {
        Gate::authorize('create', Activity::class);

        return view('content.activities.create', $this->formData(request()->user()));
    }

    public function store(StoreActivityRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $activity = Activity::query()->create($this->attributes($request, $request->validated()));
        $auditLogger->record($request->user(), 'activity.created', $request, $activity);

        return redirect()->route('activities.edit', $activity)->with('status', 'Activity created successfully.');
    }

    public function show(Activity $activity): RedirectResponse
    {
        Gate::authorize('view', $activity);

        return redirect()->route('activities.edit', $activity);
    }

    public function edit(Activity $activity): View
    {
        Gate::authorize('update', $activity);

        return view('content.activities.edit', [
            'activity' => $activity,
            ...$this->formData(request()->user()),
        ]);
    }

    public function update(
        UpdateActivityRequest $request,
        Activity $activity,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $activity->update($this->attributes($request, $request->validated(), $activity));
        $auditLogger->record($request->user(), 'activity.updated', $request, $activity);

        return redirect()->route('activities.edit', $activity)->with('status', 'Activity updated successfully.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        abort(405);
    }

    /**
     * @return array{schools: Collection<int, School>, subjects: Collection<int, Subject>}
     */
    private function formData(User $user): array
    {
        $subjects = Subject::query()
            ->when(! $user->hasRole(RoleCode::SuperAdmin), fn ($query) => $query
                ->where(fn ($scope) => $scope
                    ->whereNull('school_id')
                    ->orWhere('school_id', $user->school_id)))
            ->with([
                'skills' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with(['levels' => fn ($levels) => $levels->orderBy('level')->orderBy('id')])
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $schools = $user->hasRole(RoleCode::SuperAdmin)
            ? School::query()->orderBy('name')->orderBy('id')->get()
            : School::query()->whereKey($user->school_id)->get();

        return compact('schools', 'subjects');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(
        StoreActivityRequest|UpdateActivityRequest $request,
        array $validated,
        ?Activity $activity = null,
    ): array {
        /** @var User $user */
        $user = $request->user();
        $examples = collect(preg_split('/\R/u', (string) ($validated['examples'] ?? '')))
            ->map(fn (string $example): string => trim($example))
            ->filter()
            ->values()
            ->all();
        $status = $validated['status'];

        return [
            'school_id' => $user->hasRole(RoleCode::SuperAdmin)
                ? ($validated['school_id'] ?? null)
                : $user->school_id,
            'skill_id' => $validated['skill_id'],
            'skill_level_id' => $validated['skill_level_id'] ?? null,
            'created_by' => $activity?->created_by ?? $user->id,
            'code' => $validated['code'],
            'type' => $validated['type'],
            'title' => $validated['title'],
            'title_marathi' => $validated['title_marathi'],
            'instructions' => $validated['instructions'] ?? null,
            'instructions_marathi' => $validated['instructions_marathi'] ?? null,
            'content' => [
                'body' => $validated['content_body'] ?? null,
                'body_marathi' => $validated['content_body_marathi'] ?? null,
                'examples' => $examples,
            ],
            'difficulty' => $validated['difficulty'],
            'estimated_minutes' => $validated['estimated_minutes'] ?? null,
            'max_score' => $validated['max_score'],
            'status' => $status,
            'published_at' => $status === 'published' ? ($activity?->published_at ?? now()) : null,
        ];
    }
}
