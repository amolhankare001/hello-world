<?php

namespace App\Http\Controllers;

use App\Http\Requests\Administration\StoreSchoolClassRequest;
use App\Http\Requests\Administration\UpdateSchoolClassRequest;
use App\Models\SchoolClass;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SchoolClass::class);
        $school = request()->user()->school;

        return view('administration.classes.index', [
            'classes' => SchoolClass::query()
                ->whereBelongsTo($school)
                ->with(['divisions' => fn ($query) => $query->orderBy('name')])
                ->orderBy('grade_level')
                ->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', SchoolClass::class);

        return view('administration.classes.create');
    }

    public function store(
        StoreSchoolClassRequest $request,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validated();
        $schoolClass = DB::transaction(function () use ($request, $validated): SchoolClass {
            $schoolClass = $request->user()->school->classes()->create([
                'name' => $validated['name'],
                'name_marathi' => $validated['name_marathi'],
                'grade_level' => $validated['grade_level'],
                'is_active' => $validated['is_active'],
            ]);
            $schoolClass->divisions()->create([
                'name' => $validated['division_name'],
                'name_marathi' => $validated['division_name_marathi'],
                'is_active' => true,
            ]);

            return $schoolClass;
        });
        $auditLogger->record($request->user(), 'school_class.created', $request, $schoolClass);

        return redirect()->route('school-classes.index')->with('status', 'Class created successfully.');
    }

    public function show(SchoolClass $schoolClass): RedirectResponse
    {
        Gate::authorize('view', $schoolClass);

        return redirect()->route('school-classes.edit', $schoolClass);
    }

    public function edit(SchoolClass $schoolClass): View
    {
        Gate::authorize('update', $schoolClass);

        return view('administration.classes.edit', [
            'schoolClass' => $schoolClass->load(['divisions' => fn ($query) => $query->orderBy('name')]),
        ]);
    }

    public function update(
        UpdateSchoolClassRequest $request,
        SchoolClass $schoolClass,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $schoolClass->update($request->validated());
        $auditLogger->record($request->user(), 'school_class.updated', $request, $schoolClass);

        return redirect()->route('school-classes.edit', $schoolClass)->with('status', 'Class updated successfully.');
    }

    public function destroy(SchoolClass $schoolClass): never
    {
        abort(405);
    }
}
