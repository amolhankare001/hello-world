<?php

namespace App\Http\Controllers;

use App\Http\Requests\Simulation\SubmitSimulationEventRequest;
use App\Models\Simulation;
use App\Models\SimulationChallenge;
use App\Models\SimulationSession;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\SimulationSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentSimulationController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $student = $user->student;
        $simulations = Simulation::query()
            ->where('status', 'published')
            ->whereHas('skills')
            ->with('skills.subject:id,name,name_marathi')
            ->orderBy('title_marathi')
            ->orderBy('id')
            ->get();
        $recentSessions = $student->simulationSessions()
            ->where('status', 'completed')
            ->with(['simulation:id,code,title,title_marathi', 'result'])
            ->latest('completed_at')
            ->limit(10)
            ->get();

        return view('simulations.index', compact('simulations', 'recentSessions'));
    }

    public function start(
        Request $request,
        Simulation $simulation,
        SimulationSessionService $simulationSessionService,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $student = $user->student;
        $enrollment = $this->currentEnrollment($student);
        $session = $simulationSessionService->start($student, $enrollment->academicYear, $simulation);

        return redirect()->route('simulations.sessions.show', $session);
    }

    public function show(
        SimulationSession $simulationSession,
        SimulationSessionService $simulationSessionService,
    ): View|RedirectResponse {
        if ($simulationSession->status === 'completed') {
            return redirect()->route('simulations.sessions.result', $simulationSession);
        }

        $simulationSession->loadMissing('simulation');

        return view('simulations.show', [
            'simulationSession' => $simulationSession,
            'initialState' => $simulationSessionService->state($simulationSession),
        ]);
    }

    public function submit(
        SubmitSimulationEventRequest $request,
        SimulationSession $simulationSession,
        SimulationChallenge $simulationChallenge,
        SimulationSessionService $simulationSessionService,
    ): JsonResponse {
        $validated = $request->validated();

        return response()->json(
            $simulationSessionService->submit(
                $simulationSession,
                $simulationChallenge,
                $validated['state'],
            ),
        );
    }

    public function result(SimulationSession $simulationSession): View|RedirectResponse
    {
        if ($simulationSession->status !== 'completed') {
            return redirect()->route('simulations.sessions.show', $simulationSession);
        }

        $simulationSession->loadMissing([
            'simulation.skills.subject',
            'challenges.events',
            'result',
        ]);

        return view('simulations.result', compact('simulationSession'));
    }

    private function currentEnrollment(Student $student): StudentEnrollment
    {
        return $student->enrollments()
            ->where('status', 'active')
            ->with('academicYear')
            ->latest('enrolled_on')
            ->firstOrFail();
    }
}
