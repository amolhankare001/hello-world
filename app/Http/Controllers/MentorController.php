<?php

namespace App\Http\Controllers;

use App\Enums\RoleCode;
use App\Http\Requests\Administration\StoreMentorRequest;
use App\Http\Requests\Administration\UpdateMentorRequest;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MentorController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Mentor::class);
        $school = request()->user()->school;

        return view('administration.mentors.index', [
            'mentors' => Mentor::query()
                ->whereBelongsTo($school)
                ->with('user')
                ->withCount('studentAssignments')
                ->orderBy('employee_number')
                ->orderBy('id')
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Mentor::class);

        return view('administration.mentors.create');
    }

    public function store(
        StoreMentorRequest $request,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validated();
        $school = $request->user()->school;
        $role = Role::query()->where('code', RoleCode::Mentor)->firstOrFail();

        $mentor = DB::transaction(function () use ($role, $school, $validated): Mentor {
            $user = User::query()->create([
                'role_id' => $role->id,
                'school_id' => $school->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'preferred_locale' => 'mr',
                'is_active' => true,
            ]);

            return Mentor::query()->create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'employee_number' => $validated['employee_number'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'qualifications' => $validated['qualifications'] ?? null,
            ]);
        });
        $auditLogger->record($request->user(), 'mentor.created', $request, $mentor);

        return redirect()->route('mentors.index')->with('status', 'Mentor created successfully.');
    }

    public function show(Mentor $mentor): RedirectResponse
    {
        Gate::authorize('view', $mentor);

        return redirect()->route('mentors.edit', $mentor);
    }

    public function edit(Mentor $mentor): View
    {
        Gate::authorize('update', $mentor);

        return view('administration.mentors.edit', ['mentor' => $mentor->load('user')]);
    }

    public function update(
        UpdateMentorRequest $request,
        Mentor $mentor,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($mentor, $validated): void {
            $userAttributes = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $validated['is_active'],
            ];

            if (($validated['password'] ?? null) !== null) {
                $userAttributes['password'] = $validated['password'];
            }

            $mentor->user->update($userAttributes);
            $mentor->update([
                'employee_number' => $validated['employee_number'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'qualifications' => $validated['qualifications'] ?? null,
            ]);
        });
        $auditLogger->record($request->user(), 'mentor.updated', $request, $mentor);

        return redirect()->route('mentors.edit', $mentor)->with('status', 'Mentor updated successfully.');
    }

    public function destroy(Mentor $mentor): never
    {
        abort(405);
    }
}
