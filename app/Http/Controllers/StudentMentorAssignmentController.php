<?php

namespace App\Http\Controllers;

use App\Http\Requests\Administration\AssignStudentMentorRequest;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StudentMentorAssignmentController extends Controller
{
    public function store(
        AssignStudentMentorRequest $request,
        Student $student,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $school = $request->user()->school;
        $date = now($school->timezone)->toDateString();
        $previousAssignmentEndDate = now($school->timezone)->subDay()->toDateString();
        $academicYear = AcademicYear::query()
            ->whereBelongsTo($school)
            ->where('is_current', true)
            ->firstOrFail();

        DB::transaction(function () use ($academicYear, $date, $previousAssignmentEndDate, $request, $student): void {
            $student->mentorAssignments()
                ->activeOn($date)
                ->update(['ended_on' => $previousAssignmentEndDate]);
            $student->mentorAssignments()->create([
                'mentor_id' => $request->integer('mentor_id'),
                'academic_year_id' => $academicYear->id,
                'assigned_on' => $date,
                'is_primary' => true,
            ]);
        });
        $auditLogger->record($request->user(), 'student.mentor_assigned', $request, $student, [
            'mentor_id' => $request->integer('mentor_id'),
        ]);

        return redirect()->route('students.edit', $student)->with('status', 'Mentor assignment updated.');
    }
}
