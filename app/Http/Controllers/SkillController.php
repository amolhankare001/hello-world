<?php

namespace App\Http\Controllers;

use App\Http\Requests\Content\StoreSkillRequest;
use App\Http\Requests\Content\UpdateSkillRequest;
use App\Models\Skill;
use App\Models\Subject;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function store(
        StoreSkillRequest $request,
        Subject $subject,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $skill = $subject->skills()->create($request->validated());
        $auditLogger->record($request->user(), 'skill.created', $request, $skill);

        return redirect()->route('subjects.edit', $subject)->with('status', 'Skill created successfully.');
    }

    public function edit(Subject $subject, Skill $skill): View
    {
        Gate::authorize('update', $skill);

        $skill->load([
            'levels' => fn ($query) => $query->orderBy('level')->orderBy('id'),
        ]);
        $parentSkills = $subject->skills()
            ->whereKeyNot($skill->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('content.skills.edit', compact('parentSkills', 'skill', 'subject'));
    }

    public function update(
        UpdateSkillRequest $request,
        Subject $subject,
        Skill $skill,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $skill->update($request->validated());
        $auditLogger->record($request->user(), 'skill.updated', $request, $skill);

        return redirect()
            ->route('subjects.skills.edit', [$subject, $skill])
            ->with('status', 'Skill updated successfully.');
    }
}
