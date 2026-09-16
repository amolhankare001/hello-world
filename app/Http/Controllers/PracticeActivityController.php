<?php

namespace App\Http\Controllers;

use App\Http\Requests\Content\UpdatePracticeActivityRequest;
use App\Models\Activity;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;

class PracticeActivityController extends Controller
{
    public function update(
        UpdatePracticeActivityRequest $request,
        Activity $activity,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validated();
        $practiceActivity = $activity->practiceActivity()->firstOrCreate();
        $practiceActivity->update([
            'question_count' => $validated['question_count'],
            'randomize_questions' => $validated['randomize_questions'],
            'show_feedback_immediately' => $validated['show_feedback_immediately'],
            'configuration' => [
                'difficulty_up_accuracy' => (float) $validated['difficulty_up_accuracy'],
                'remedial_accuracy' => (float) $validated['remedial_accuracy'],
                'minimum_difficulty' => (int) $validated['minimum_difficulty'],
                'maximum_difficulty' => (int) $validated['maximum_difficulty'],
            ],
        ]);
        $auditLogger->record($request->user(), 'practice_activity.updated', $request, $practiceActivity);

        return redirect()->route('activities.edit', $activity)->with('status', 'Practice settings updated.');
    }
}
