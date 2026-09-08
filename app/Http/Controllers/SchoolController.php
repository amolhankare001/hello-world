<?php

namespace App\Http\Controllers;

use App\Http\Requests\Administration\StoreSchoolRequest;
use App\Http\Requests\Administration\UpdateSchoolRequest;
use App\Models\School;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', School::class);

        return view('administration.schools.index', [
            'schools' => School::query()
                ->withCount(['students', 'mentors'])
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', School::class);

        return view('administration.schools.create');
    }

    public function store(StoreSchoolRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $school = School::query()->create($request->validated());
        $auditLogger->record($request->user(), 'school.created', $request, $school);

        return redirect()->route('schools.index')->with('status', 'School created successfully.');
    }

    public function show(School $school): RedirectResponse
    {
        Gate::authorize('view', $school);

        return redirect()->route('schools.edit', $school);
    }

    public function edit(School $school): View
    {
        Gate::authorize('update', $school);

        return view('administration.schools.edit', ['school' => $school]);
    }

    public function update(
        UpdateSchoolRequest $request,
        School $school,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $school->update($request->validated());
        $auditLogger->record($request->user(), 'school.updated', $request, $school);

        return redirect()->route('schools.index')->with('status', 'School updated successfully.');
    }

    public function destroy(School $school): never
    {
        abort(405);
    }
}
