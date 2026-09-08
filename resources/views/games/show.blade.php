<x-layouts.app :title="$gameSession->game->title_marathi">
    <div
        class="game-shell"
        data-game-root
        data-state-element="game-state-{{ $gameSession->id }}"
        data-answer-url="{{ route('games.sessions.answer', [$gameSession, '__QUESTION__']) }}"
        data-finish-url="{{ route('games.sessions.finish', $gameSession) }}"
        data-result-url="{{ route('games.sessions.result', $gameSession) }}"
    >
        <header class="game-toolbar portal-card mb-3 p-3">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div>
                    <a class="small text-decoration-none" href="{{ route('games.index') }}">← खेळांची यादी</a>
                    <h1 class="h4 portal-brand mb-0">{{ $gameSession->game->title_marathi }}</h1>
                </div>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-game-sound aria-pressed="true">
                    <i class="bi bi-volume-up" aria-hidden="true"></i>
                    <span>आवाज सुरू</span>
                </button>
            </div>
        </header>

        <section class="game-stage portal-card" aria-live="polite">
            <div class="game-stats">
                <div>
                    <span class="game-stat-label">गुण</span>
                    <strong data-game-score>0</strong>
                </div>
                <div>
                    <span class="game-stat-label">संधी</span>
                    <strong data-game-lives>♥ ♥ ♥</strong>
                </div>
                <div>
                    <span class="game-stat-label">वेळ</span>
                    <strong data-game-timer>--</strong>
                </div>
                <div>
                    <span class="game-stat-label">प्रगती</span>
                    <strong data-game-progress>0 / 0</strong>
                </div>
            </div>

            <div class="game-progress-track" role="progressbar" aria-label="खेळाची प्रगती" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <span data-game-progress-bar></span>
            </div>

            <div class="game-prompt-wrap">
                <span class="badge rounded-pill text-bg-light text-primary" data-game-level></span>
                <h2 class="game-prompt" data-game-prompt>खेळ तयार होत आहे...</h2>
                <p class="text-secondary mb-0">योग्य कार्डला स्पर्श करा.</p>
            </div>

            <div class="game-feedback" data-game-feedback hidden></div>
            <div class="game-choice-field" data-game-choices></div>
            <div class="game-loading text-center" data-game-loading hidden>
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading</span></div>
            </div>
        </section>

        <noscript>
            <div class="alert alert-warning mt-3">हा खेळ खेळण्यासाठी JavaScript सुरू असणे आवश्यक आहे.</div>
        </noscript>
    </div>

    <script type="application/json" id="game-state-{{ $gameSession->id }}">@json($initialState)</script>
</x-layouts.app>
