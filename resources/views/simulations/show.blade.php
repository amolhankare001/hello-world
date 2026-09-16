<x-layouts.app :title="$simulationSession->simulation->title_marathi">
    <div
        class="simulation-shell"
        data-simulation-root
        data-state-element="simulation-state-{{ $simulationSession->id }}"
        data-submit-url="{{ route('simulations.sessions.submit', [$simulationSession, '__CHALLENGE__']) }}"
        data-result-url="{{ route('simulations.sessions.result', $simulationSession) }}"
    >
        <header class="simulation-toolbar portal-card mb-3 p-3">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div>
                    <a class="small text-decoration-none" href="{{ route('simulations.index') }}">← अनुकरणांची यादी</a>
                    <h1 class="h4 portal-brand mb-0">{{ $simulationSession->simulation->title_marathi }}</h1>
                </div>
                <span class="badge rounded-pill text-bg-success" data-simulation-difficulty></span>
            </div>
        </header>

        <section class="simulation-stage portal-card" aria-live="polite">
            <div class="simulation-stats">
                <div><span>गुण</span><strong data-simulation-score>0</strong></div>
                <div><span>यशस्वी</span><strong data-simulation-success>0</strong></div>
                <div><span>प्रगती</span><strong data-simulation-progress>0 / 0</strong></div>
                <div><span>प्रयत्न</span><strong data-simulation-attempts>0 / 3</strong></div>
            </div>
            <div class="simulation-progress-track" role="progressbar" aria-label="अनुकरणाची प्रगती" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <span data-simulation-progress-bar></span>
            </div>

            <div class="simulation-prompt-wrap">
                <p class="simulation-context" data-simulation-context hidden></p>
                <div class="simulation-target" data-simulation-target hidden></div>
                <h2 class="simulation-prompt" data-simulation-prompt>अनुकरण तयार होत आहे...</h2>
                <p class="text-secondary mb-0">वस्तूंना स्पर्श करून किंवा ओढून कृती पूर्ण करा.</p>
            </div>

            <div class="simulation-feedback" data-simulation-feedback hidden></div>
            <div class="simulation-workspace" data-simulation-workspace></div>
            <div class="d-grid d-sm-flex justify-content-sm-center gap-2 mt-4">
                <button class="btn btn-success btn-lg px-5" type="button" data-simulation-submit>
                    उत्तर तपासा
                </button>
                <button class="btn btn-outline-secondary btn-lg" type="button" data-simulation-reset>
                    पुन्हा मांडणी करा
                </button>
            </div>
            <div class="simulation-loading text-center mt-3" data-simulation-loading hidden>
                <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading</span></div>
            </div>
        </section>

        <noscript>
            <div class="alert alert-warning mt-3">हे अनुकरण वापरण्यासाठी JavaScript सुरू असणे आवश्यक आहे.</div>
        </noscript>
    </div>

    <script type="application/json" id="simulation-state-{{ $simulationSession->id }}">@json($initialState)</script>
</x-layouts.app>
