<?php

namespace App\Http\Controllers;

use App\Enums\RoleCode;
use App\Http\Requests\Administration\StoreStudentRequest;
use App\Http\Requests\Administration\UpdateStudentRequest;
use App\Models\AcademicYear;
use App\Models\Division;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\Skill;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Student::class);
        $school = request()->user()->school;
        $date = now($school->timezone)->toDateString();

        return view('administration.students.index', [
            'students' => Student::query()
                ->whereBelongsTo($school)
                ->with([
                    'user',
                    'enrollments' => fn ($query) => $query
                        ->where('status', 'active')
                        ->with('division.schoolClass'),
                    'mentorAssignments' => fn ($query) => $query
                        ->activeOn($date)
                        ->with('mentor.user'),
                ])
                ->orderBy('student_number')
                ->orderBy('id')
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Student::class);

        return view('administration.students.create', $this->formData());
    }

    public function store(
        StoreStudentRequest $request,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validated();
        $school = $request->user()->school;
        $academicYear = $this->currentAcademicYear($school->id);
        $role = Role::query()->where('code', RoleCode::Student)->firstOrFail();

        $student = DB::transaction(function () use ($academicYear, $role, $school, $validated): Student {
            $user = User::query()->create([
                'role_id' => $role->id,
                'school_id' => $school->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'preferred_locale' => 'mr',
                'is_active' => true,
            ]);
            $student = Student::query()->create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'student_number' => $validated['student_number'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'joined_on' => $validated['joined_on'],
                'guardian_name' => $validated['guardian_name'] ?? null,
                'guardian_phone' => $validated['guardian_phone'] ?? null,
            ]);
            $student->enrollments()->create([
                'academic_year_id' => $academicYear->id,
                'division_id' => $validated['division_id'],
                'roll_number' => $validated['roll_number'] ?? null,
                'enrolled_on' => $validated['joined_on'],
                'status' => 'active',
            ]);

            if (($validated['mentor_id'] ?? null) !== null) {
                $student->mentorAssignments()->create([
                    'mentor_id' => $validated['mentor_id'],
                    'academic_year_id' => $academicYear->id,
                    'assigned_on' => $validated['joined_on'],
                    'is_primary' => true,
                ]);
            }

            $student->skillProgress()->createMany(
                Skill::query()->orderBy('id')->get()->map(fn (Skill $skill): array => [
                    'skill_id' => $skill->id,
                    'academic_year_id' => $academicYear->id,
                ])->all(),
            );

            return $student;
        });
        $auditLogger->record($request->user(), 'student.created', $request, $student);

        return redirect()->route('students.index')->with('status', 'Student created successfully.');
    }

    public function show(Student $student): RedirectResponse
    {
        Gate::authorize('view', $student);

        return redirect()->route('students.edit', $student);
    }

    public function edit(Student $student): View
    {
        Gate::authorize('update', $student);
        $school = request()->user()->school;
        $date = now($school->timezone)->toDateString();

        return view('administration.students.edit', [
            'student' => $student->load([
                'user',
                'enrollments' => fn ($query) => $query->where('status', 'active')->with('division.schoolClass'),
                'mentorAssignments' => fn ($query) => $query->activeOn($date)->with('mentor.user'),
            ]),
            'mentors' => Mentor::query()->whereBelongsTo($school)->with('user')->orderBy('employee_number')->get(),
        ]);
    }

    public function update(
        UpdateStudentRequest $request,
        Student $student,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($student, $validated): void {
            $userAttributes = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $validated['is_active'],
            ];

            if (($validated['password'] ?? null) !== null) {
                $userAttributes['password'] = $validated['password'];
            }

            $student->user->update($userAttributes);
            $student->update([
                'student_number' => $validated['student_number'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'joined_on' => $validated['joined_on'],
                'guardian_name' => $validated['guardian_name'] ?? null,
                'guardian_phone' => $validated['guardian_phone'] ?? null,
            ]);
        });
        $auditLogger->record($request->user(), 'student.updated', $request, $student);

        return redirect()->route('students.edit', $student)->with('status', 'Student updated successfully.');
    }

    public function destroy(Student $student): never
    {
        abort(405);
    }

    /**
     * @return array{divisions: Collection<int, Division>, mentors: Collection<int, Mentor>}
     */
    private function formData(): array
    {
        $school = request()->user()->school;

        return [
            'divisions' => Division::query()
                ->whereHas('schoolClass', fn ($query) => $query->whereBelongsTo($school))
                ->with('schoolClass')
                ->orderBy('school_class_id')
                ->orderBy('name')
                ->get(),
            'mentors' => Mentor::query()
                ->whereBelongsTo($school)
                ->with('user')
                ->orderBy('employee_number')
                ->get(),
        ];
    }

    private function currentAcademicYear(int $schoolId): AcademicYear
    {
        return AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('is_current', true)
            ->firstOrFail();
    }
}
