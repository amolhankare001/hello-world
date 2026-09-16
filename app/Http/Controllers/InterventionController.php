<?php

namespace App\Http\Controllers;

use App\Http\Requests\Mentor\StoreInterventionRequest;
use App\Http\Requests\Mentor\UpdateInterventionRequest;
use App\Models\Intervention;
use App\Models\LearningRecommendation;
use App\Models\Mentor;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InterventionController extends Controller
{
    public function store(StoreInterventionRequest $request, Student $student): RedirectResponse
    {
        /** @var Mentor $mentor */
        $mentor = $request->user()->mentor;
        $data = $request->validated();
        $date = now($request->user()->school->timezone)->toDateString();

        StudentMentorAssignment::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($mentor)
            ->where('academic_year_id', $data['academic_year_id'])
            ->activeOn($date)
            ->firstOrFail();

        if ($data['skill_id'] !== null) {
            Skill::query()
                ->whereKey($data['skill_id'])
                ->whereHas('subject', fn (Builder $query): Builder => $query
                    ->whereNull('school_id')
                    ->orWhere('school_id', $student->school_id))
                ->firstOrFail();
        }

        Intervention::query()->create([
            ...$data,
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'status' => 'planned',
        ]);

        return redirect()
            ->route('mentor.students.show', $student)
            ->with('status', 'नवीन हस्तक्षेप योजना तयार केली.');
    }

    public function edit(Intervention $intervention): View
    {
        Gate::authorize('update', $intervention);

        return view('mentor.interventions.edit', [
            'intervention' => $intervention->load(['student.user', 'skill', 'activities']),
        ]);
    }

    public function update(
        UpdateInterventionRequest $request,
        Intervention $intervention,
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(function () use ($data, $intervention): void {
            $intervention->update([
                'title' => $data['title'],
                'reason' => $data['reason'],
                'plan' => $data['plan'],
                'status' => $data['status'],
                'starts_on' => $data['starts_on'],
                'target_completion_on' => $data['target_completion_on'] ?? null,
                'completed_on' => $data['status'] === 'completed' ? $data['completed_on'] : null,
                'outcome' => $data['status'] === 'completed' ? $data['outcome'] : null,
            ]);

            foreach ($data['activities'] ?? [] as $activityData) {
                $intervention->activities()
                    ->whereKey($activityData['id'])
                    ->update([
                        'completed_on' => $activityData['completed_on'] ?? null,
                        'mentor_notes' => $activityData['mentor_notes'] ?? null,
                    ]);
            }

            if ($data['status'] === 'completed' && $intervention->recommendation !== null) {
                $intervention->recommendation->update([
                    'status' => LearningRecommendation::STATUS_RESOLVED,
                ]);
            }
        });

        return redirect()
            ->route('mentor.students.show', $intervention->student)
            ->with('status', 'हस्तक्षेप प्रगती जतन केली.');
    }
}
