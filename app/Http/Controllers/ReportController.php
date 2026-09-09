<?php

namespace App\Http\Controllers;

use App\Enums\RoleCode;
use App\Http\Requests\ReportFilterRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Skill;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\ProgressReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.index', [
            'reports' => $this->reportTitles(),
            ...$this->filterOptions($request->user()),
        ]);
    }

    public function show(
        ReportFilterRequest $request,
        string $report,
        ProgressReportService $reports,
    ): View {
        $filters = $request->safe()->except('report_type');
        $students = $this->accessibleStudents($request->user())
            ->when(
                isset($filters['student_id']),
                fn (Builder $query) => $query->whereKey($filters['student_id']),
            )
            ->with(['user', 'school'])
            ->orderBy('id')
            ->get();

        if (isset($filters['student_id']) && $students->isEmpty()) {
            abort(404);
        }

        $this->ensureFilterAccess($request->user(), $filters);
        $data = $reports->build($students, $filters);

        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'school_id' => $request->user()->school_id,
            'action' => 'report.generated',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'metadata' => ['report_type' => $report, 'filters' => $filters],
            'created_at' => now(),
        ]);

        return view('reports.show', [
            'reportType' => $report,
            'reportTitle' => $this->reportTitles()[$report],
            'students' => $students,
            'filters' => $filters,
            ...$this->filterOptions($request->user()),
            ...$data,
        ]);
    }

    /**
     * @return Builder<Student>
     */
    private function accessibleStudents(User $user): Builder
    {
        $query = Student::query();

        if ($user->hasRole(RoleCode::SuperAdmin)) {
            return $query;
        }

        if ($user->hasRole(RoleCode::SchoolAdmin)) {
            return $query->whereBelongsTo($user->school);
        }

        if ($user->hasRole(RoleCode::Student)) {
            return $query->where('user_id', $user->id);
        }

        $date = now($user->school->timezone)->toDateString();

        return $query->whereIn(
            'id',
            $user->mentor->studentAssignments()
                ->activeOn($date)
                ->select('student_id'),
        );
    }

    /**
     * @return array{
     *     filterStudents: Collection<int, Student>,
     *     academicYears: Collection<int, AcademicYear>,
     *     subjects: Collection<int, Subject>,
     *     skills: Collection<int, Skill>
     * }
     */
    private function filterOptions(User $user): array
    {
        $schoolId = $user->school_id;
        $subjects = Subject::query()
            ->where('is_active', true)
            ->when(
                ! $user->hasRole(RoleCode::SuperAdmin),
                fn (Builder $query) => $query->where(
                    fn (Builder $scope) => $scope
                        ->whereNull('school_id')
                        ->orWhere('school_id', $schoolId),
                ),
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return [
            'filterStudents' => $this->accessibleStudents($user)
                ->with('user')
                ->orderBy('id')
                ->get(),
            'academicYears' => AcademicYear::query()
                ->when(
                    ! $user->hasRole(RoleCode::SuperAdmin),
                    fn (Builder $query) => $query->where('school_id', $schoolId),
                )
                ->orderByDesc('starts_on')
                ->get(),
            'subjects' => $subjects,
            'skills' => Skill::query()
                ->where('is_active', true)
                ->whereIn('subject_id', $subjects->modelKeys())
                ->with('subject')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function ensureFilterAccess(User $user, array $filters): void
    {
        if ($user->hasRole(RoleCode::SuperAdmin)) {
            return;
        }

        if (isset($filters['academic_year_id'])) {
            abort_unless(
                AcademicYear::query()
                    ->whereKey($filters['academic_year_id'])
                    ->where('school_id', $user->school_id)
                    ->exists(),
                404,
            );
        }

        if (isset($filters['subject_id'])) {
            abort_unless(
                Subject::query()
                    ->whereKey($filters['subject_id'])
                    ->where(fn (Builder $query) => $query
                        ->whereNull('school_id')
                        ->orWhere('school_id', $user->school_id))
                    ->exists(),
                404,
            );
        }

        if (isset($filters['skill_id'])) {
            abort_unless(
                Skill::query()
                    ->whereKey($filters['skill_id'])
                    ->whereHas('subject', fn (Builder $query) => $query
                        ->whereNull('school_id')
                        ->orWhere('school_id', $user->school_id))
                    ->exists(),
                404,
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function reportTitles(): array
    {
        return [
            'student_progress' => 'विद्यार्थी प्रगती अहवाल',
            'holistic_progress' => 'समग्र प्रगती अहवाल',
            'pre_test' => 'पूर्व-चाचणी अहवाल',
            'post_test' => 'उत्तर-चाचणी अहवाल',
            'pre_post_improvement' => 'पूर्व/उत्तर सुधारणा अहवाल',
            'skill_wise' => 'कौशल्यनिहाय अहवाल',
            'game_performance' => 'खेळ कामगिरी अहवाल',
            'practice' => 'सराव अहवाल',
            'class_group' => 'वर्ग/गट अहवाल',
            'mentor_intervention' => 'मार्गदर्शक हस्तक्षेप अहवाल',
        ];
    }
}
