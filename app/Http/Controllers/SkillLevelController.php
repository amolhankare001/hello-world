<?php

namespace App\Http\Controllers;

use App\Http\Requests\Content\StoreSkillLevelRequest;
use App\Http\Requests\Content\UpdateSkillLevelRequest;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\Subject;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;

class SkillLevelController extends Controller
{
    public function store(
        StoreSkillLevelRequest $request,
        Subject $subject,
        Skill $skill,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $skillLevel = $skill->levels()->create($request->validated());
        $auditLogger->record($request->user(), 'skill_level.created', $request, $skillLevel);

        return redirect()
            ->route('subjects.skills.edit', [$subject, $skill])
            ->with('status', 'Level created successfully.');
    }

    public function update(
        UpdateSkillLevelRequest $request,
        Subject $subject,
        Skill $skill,
        SkillLevel $skillLevel,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $skillLevel->update($request->validated());
        $auditLogger->record($request->user(), 'skill_level.updated', $request, $skillLevel);

        return redirect()
            ->route('subjects.skills.edit', [$subject, $skill])
            ->with('status', 'Level updated successfully.');
    }
}
