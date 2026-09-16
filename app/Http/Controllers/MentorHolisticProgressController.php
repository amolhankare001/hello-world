<?php

namespace App\Http\Controllers;

use App\Http\Requests\Mentor\StoreMentorObservationRequest;
use App\Models\HolisticDomain;
use App\Models\HolisticIndicator;
use App\Models\HolisticRecord;
use App\Models\Mentor;
use App\Models\MentorObservation;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MentorHolisticProgressController extends Controller
{
    public function create(Request $request, Student $student): View
    {
        Gate::authorize('view', $student);
        $assignment = $this->activeAssignment($request, $student);

        return view('mentor.holistic.create', [
            'student' => $student->load('user'),
            'academicYear' => $assignment->academicYear,
            'domains' => HolisticDomain::query()
                ->where('is_active', true)
                ->with(['indicators' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(
        StoreMentorObservationRequest $request,
        Student $student,
    ): RedirectResponse {
        $assignment = $this->activeAssignment($request, $student);
        /** @var Mentor $mentor */
        $mentor = $request->user()->mentor;
        $data = $request->validated();

        DB::transaction(function () use ($data, $student, $mentor, $assignment): void {
            MentorObservation::query()->create([
                'student_id' => $student->id,
                'mentor_id' => $mentor->id,
                'academic_year_id' => $assignment->academic_year_id,
                'holistic_domain_id' => $data['holistic_domain_id'] ?? null,
                'observed_on' => $data['observed_on'],
                'category' => $data['category'],
                'observation' => $data['observation'],
                'strengths' => $data['strengths'] ?? null,
                'areas_for_improvement' => $data['areas_for_improvement'] ?? null,
                'recommended_intervention' => $data['recommended_intervention'] ?? null,
                'next_learning_goal' => $data['next_learning_goal'] ?? null,
                'visibility' => 'school_team',
            ]);

            $indicators = HolisticIndicator::query()
                ->where('is_active', true)
                ->whereKey(collect($data['ratings'])->pluck('indicator_id'))
                ->get()
                ->keyBy('id');
            $recordedAt = now();

            foreach ($data['ratings'] as $rating) {
                $indicator = $indicators->get($rating['indicator_id']);

                if ($indicator === null) {
                    continue;
                }

                HolisticRecord::query()->create([
                    'student_id' => $student->id,
                    'mentor_id' => $mentor->id,
                    'academic_year_id' => $assignment->academic_year_id,
                    'holistic_indicator_id' => $indicator->id,
                    'rating' => $rating['rating'],
                    'notes' => $rating['notes'] ?? null,
                    'recorded_at' => $recordedAt,
                ]);
            }
        });

        return redirect()
            ->route('mentor.students.show', $student)
            ->with('status', 'समग्र निरीक्षण आणि रेटिंग जतन केले.');
    }

    private function activeAssignment(
        Request $request,
        Student $student,
    ): StudentMentorAssignment {
        /** @var Mentor $mentor */
        $mentor = $request->user()->mentor;
        $date = now($request->user()->school->timezone)->toDateString();

        return StudentMentorAssignment::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($mentor)
            ->activeOn($date)
            ->with('academicYear')
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->firstOrFail();
    }
}
