<?php

namespace App\Http\Controllers;

use App\Http\Requests\Game\SubmitGameAnswerRequest;
use App\Models\Game;
use App\Models\GameQuestion;
use App\Models\GameSession;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\GameSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentGameController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $student = $user->student;
        $games = Game::query()
            ->where('status', 'published')
            ->whereHas('levels')
            ->whereHas('skills')
            ->with([
                'levels' => fn ($query) => $query->orderBy('level'),
                'skills.subject:id,name,name_marathi',
            ])
            ->orderBy('title_marathi')
            ->orderBy('id')
            ->get();
        $recentSessions = $student->gameSessions()
            ->where('status', 'completed')
            ->with(['game:id,code,title,title_marathi', 'result'])
            ->latest('completed_at')
            ->limit(10)
            ->get();

        return view('games.index', compact('games', 'recentSessions'));
    }

    public function start(
        Request $request,
        Game $game,
        GameSessionService $gameSessionService,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $student = $user->student;
        $enrollment = $this->currentEnrollment($student);
        $session = $gameSessionService->start($student, $enrollment->academicYear, $game);

        return redirect()->route('games.sessions.show', $session);
    }

    public function show(
        GameSession $gameSession,
        GameSessionService $gameSessionService,
    ): View|RedirectResponse {
        if ($gameSession->status === 'completed') {
            return redirect()->route('games.sessions.result', $gameSession);
        }

        $gameSession->loadMissing(['game', 'level']);

        return view('games.show', [
            'gameSession' => $gameSession,
            'initialState' => $gameSessionService->state($gameSession),
        ]);
    }

    public function answer(
        SubmitGameAnswerRequest $request,
        GameSession $gameSession,
        GameQuestion $gameQuestion,
        GameSessionService $gameSessionService,
    ): JsonResponse {
        $validated = $request->validated();

        return response()->json(
            $gameSessionService->answer($gameSession, $gameQuestion, $validated['answer']),
        );
    }

    public function finish(
        GameSession $gameSession,
        GameSessionService $gameSessionService,
    ): JsonResponse {
        return response()->json($gameSessionService->finishExpired($gameSession));
    }

    public function result(GameSession $gameSession): View|RedirectResponse
    {
        if ($gameSession->status !== 'completed') {
            return redirect()->route('games.sessions.show', $gameSession);
        }

        $gameSession->loadMissing([
            'game.skills.subject',
            'level',
            'questions.answer',
            'result',
        ]);

        return view('games.result', compact('gameSession'));
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
