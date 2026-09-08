<?php

namespace App\Http\Controllers;

use App\Http\Requests\Mentor\ModifyLearningRecommendationRequest;
use App\Http\Requests\Mentor\ReviewLearningRecommendationRequest;
use App\Models\LearningRecommendation;
use App\Models\Mentor;
use App\Services\LearningRecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearningRecommendationController extends Controller
{
    public function edit(LearningRecommendation $learningRecommendation): View
    {
        Gate::authorize('update', $learningRecommendation);

        return view('mentor.recommendations.edit', [
            'recommendation' => $learningRecommendation->load(['student.user', 'skill', 'items']),
        ]);
    }

    public function update(
        ModifyLearningRecommendationRequest $request,
        LearningRecommendation $learningRecommendation,
        LearningRecommendationService $recommendations,
    ): RedirectResponse {
        $recommendations->modify($learningRecommendation, $request->user(), $request->validated());

        return redirect()
            ->route('mentor.students.show', $learningRecommendation->student)
            ->with('status', 'शिफारस बदल जतन केले.');
    }

    public function accept(
        ReviewLearningRecommendationRequest $request,
        LearningRecommendation $learningRecommendation,
        LearningRecommendationService $recommendations,
    ): RedirectResponse {
        /** @var Mentor $mentor */
        $mentor = $request->user()->mentor;
        $recommendations->accept(
            $learningRecommendation,
            $mentor,
            $request->user(),
            $request->validated('mentor_notes'),
        );

        return redirect()
            ->route('mentor.students.show', $learningRecommendation->student)
            ->with('status', 'शिफारस स्वीकारून हस्तक्षेप योजना तयार केली.');
    }

    public function reject(
        ReviewLearningRecommendationRequest $request,
        LearningRecommendation $learningRecommendation,
        LearningRecommendationService $recommendations,
    ): RedirectResponse {
        $recommendations->reject(
            $learningRecommendation,
            $request->user(),
            $request->validated('mentor_notes'),
        );

        return redirect()
            ->route('mentor.students.show', $learningRecommendation->student)
            ->with('status', 'शिफारस नाकारली.');
    }
}
