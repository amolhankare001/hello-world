<x-layouts.app title="Game result">
    @php
        $result = $gameSession->result;
    @endphp
    <section class="game-result-hero portal-card mb-4 p-4 p-lg-5 text-center">
        <span class="badge rounded-pill text-bg-success mb-3">खेळ पूर्ण</span>
        <div class="game-result-icon mx-auto mb-3" aria-hidden="true">🏆</div>
        <h1 class="h2 portal-brand mb-1">{{ $gameSession->game->title_marathi }}</h1>
        <p class="text-secondary">{{ $gameSession->game->title }}</p>
        <div class="row g-3 justify-content-center mt-3">
            <div class="col-6 col-md-3">
                <div class="game-result-stat">
                    <strong>{{ $result->score }}</strong>
                    <span>एकूण गुण</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="game-result-stat">
                    <strong>{{ number_format((float) $result->accuracy, 0) }}%</strong>
                    <span>अचूकता</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="game-result-stat">
                    <strong>{{ $result->correct_count }}/{{ $gameSession->questions->count() }}</strong>
                    <span>बरोबर</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="game-result-stat">
                    <strong>+{{ $result->xp_awarded }}</strong>
                    <span>XP मिळाले</span>
                </div>
            </div>
        </div>
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 mt-4">
            <form method="POST" action="{{ route('games.start', $gameSession->game) }}">
                @csrf
                <button class="btn btn-primary btn-lg" type="submit">पुन्हा खेळा</button>
            </form>
            <a class="btn btn-outline-primary btn-lg" href="{{ route('games.index') }}">दुसरा खेळ निवडा</a>
        </div>
    </section>

    <section class="card portal-card">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">माझ्या उत्तरांचा आढावा</h2>
        </div>
        <div class="list-group list-group-flush">
            @foreach ($gameSession->questions as $question)
                @php
                    $submittedValue = data_get($question->answer?->answer, 'value');
                    $expectedValue = data_get($question->expected_answer, 'value');
                    $submittedLabel = collect($question->choices)->firstWhere('value', $submittedValue)['label'] ?? null;
                    $expectedLabel = collect($question->choices)->firstWhere('value', $expectedValue)['label'] ?? $expectedValue;
                    $isCorrect = $question->answer?->is_correct === true;
                @endphp
                <article class="list-group-item p-3 p-md-4">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h3 class="h6 mb-1">{{ $question->prompt_marathi }}</h3>
                            <div class="small text-secondary">
                                तुमचे उत्तर: {{ $submittedLabel ?? 'उत्तर दिले नाही' }}
                                @unless ($isCorrect)
                                    · योग्य उत्तर: {{ $expectedLabel }}
                                @endunless
                            </div>
                        </div>
                        <span class="badge text-bg-{{ $isCorrect ? 'success' : 'secondary' }}">
                            {{ $isCorrect ? 'बरोबर' : 'सराव करूया' }}
                        </span>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.app>
