<?php

namespace App\Http\Controllers;

use App\Http\Requests\Administration\StoreDivisionRequest;
use App\Http\Requests\Administration\UpdateDivisionRequest;
use App\Models\Division;
use App\Models\SchoolClass;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;

class DivisionController extends Controller
{
    public function store(
        StoreDivisionRequest $request,
        SchoolClass $schoolClass,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $division = $schoolClass->divisions()->create($request->validated());
        $auditLogger->record($request->user(), 'division.created', $request, $division);

        return redirect()->route('school-classes.edit', $schoolClass)->with('status', 'Division added successfully.');
    }

    public function update(
        UpdateDivisionRequest $request,
        SchoolClass $schoolClass,
        Division $division,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $division->update($request->validated());
        $auditLogger->record($request->user(), 'division.updated', $request, $division);

        return redirect()->route('school-classes.edit', $schoolClass)->with('status', 'Division updated successfully.');
    }
}
