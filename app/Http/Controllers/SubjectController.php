<?php

namespace App\Http\Controllers;

use App\Http\Requests\Content\StoreSubjectRequest;
use App\Http\Requests\Content\UpdateSubjectRequest;
use App\Models\Subject;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Subject::class);

        $subjects = Subject::query()
            ->withCount('skills')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(15);

        return view('content.subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        Gate::authorize('create', Subject::class);

        return view('content.subjects.create');
    }

    public function store(StoreSubjectRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $subject = Subject::query()->create([
            'school_id' => null,
            ...$request->validated(),
        ]);
        $auditLogger->record($request->user(), 'subject.created', $request, $subject);

        return redirect()->route('subjects.edit', $subject)->with('status', 'Subject created successfully.');
    }

    public function show(Subject $subject): RedirectResponse
    {
        Gate::authorize('view', $subject);

        return redirect()->route('subjects.edit', $subject);
    }

    public function edit(Subject $subject): View
    {
        Gate::authorize('update', $subject);

        $subject->load([
            'skills' => fn ($query) => $query
                ->withCount(['levels', 'activities'])
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        return view('content.subjects.edit', compact('subject'));
    }

    public function update(
        UpdateSubjectRequest $request,
        Subject $subject,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $subject->update($request->validated());
        $auditLogger->record($request->user(), 'subject.updated', $request, $subject);

        return redirect()->route('subjects.edit', $subject)->with('status', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        abort(405);
    }
}
