<x-layouts.app title="Simulation result">
    @php
        $result = $simulationSession->result;
    @endphp
    <section class="simulation-result-hero portal-card mb-4 p-4 p-lg-5 text-center">
        <span class="badge rounded-pill text-bg-success mb-3">अनुकरण पूर्ण</span>
        <div class="simulation-result-icon mx-auto mb-3" aria-hidden="true">🧩</div>
        <h1 class="h2 portal-brand mb-1">{{ $simulationSession->simulation->title_marathi }}</h1>
        <p class="text-secondary">{{ $simulationSession->simulation->title }}</p>
        <div class="row g-3 justify-content-center mt-3">
            <div class="col-6 col-md-3"><div class="game-result-stat"><strong>{{ $result->score }}</strong><span>एकूण गुण</span></div></div>
            <div class="col-6 col-md-3"><div class="game-result-stat"><strong>{{ number_format((float) $result->accuracy, 0) }}%</strong><span>अचूकता</span></div></div>
            <div class="col-6 col-md-3"><div class="game-result-stat"><strong>{{ $result->successful_count }}/{{ $simulationSession->challenges->count() }}</strong><span>यशस्वी कृती</span></div></div>
            <div class="col-6 col-md-3"><div class="game-result-stat"><strong>+{{ $result->xp_awarded }}</strong><span>XP मिळाले</span></div></div>
        </div>
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 mt-4">
            <form method="POST" action="{{ route('simulations.start', $simulationSession->simulation) }}">
                @csrf
                <button class="btn btn-success btn-lg" type="submit">पुन्हा करून पहा</button>
            </form>
            <a class="btn btn-outline-success btn-lg" href="{{ route('simulations.index') }}">दुसरे अनुकरण निवडा</a>
        </div>
    </section>

    <section class="card portal-card">
        <div class="card-header bg-white"><h2 class="h5 mb-0">माझ्या कृतींचा आढावा</h2></div>
        <div class="list-group list-group-flush">
            @foreach ($simulationSession->challenges as $challenge)
                @php
                    $successfulEvent = $challenge->events->firstWhere('is_success', true);
                    $isSuccessful = $successfulEvent !== null;
                @endphp
                <article class="list-group-item p-3 p-md-4">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h3 class="h6 mb-1">{{ $challenge->prompt_marathi }}</h3>
                            <div class="small text-secondary">
                                {{ $challenge->events->count() }} प्रयत्न
                                @if ($successfulEvent)
                                    · {{ $successfulEvent->score }} गुण
                                @endif
                            </div>
                        </div>
                        <span class="badge text-bg-{{ $isSuccessful ? 'success' : 'secondary' }}">
                            {{ $isSuccessful ? 'पूर्ण' : 'पुन्हा सराव करूया' }}
                        </span>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.app>
