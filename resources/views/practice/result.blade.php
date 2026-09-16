<x-layouts.app title="Practice result">
    <div class="card portal-card mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-4">
                <div class="col-md"><span class="badge text-bg-success mb-2">सराव पूर्ण</span><h1 class="h2 portal-brand">{{ $attempt->practiceActivity->activity->title_marathi }}</h1><p class="text-secondary mb-0">{{ $attempt->practiceActivity->activity->title }}</p></div>
                <div class="col-md-auto text-md-end"><div class="display-4 fw-bold text-primary">{{ number_format((float) $attempt->accuracy, 0) }}%</div><div>{{ $attempt->correct_count }} / {{ $attempt->correct_count + $attempt->incorrect_count }} बरोबर · {{ $attempt->duration_seconds }} सेकंद</div></div>
            </div>
        </div>
    </div>

    <div class="vstack gap-3">
        @foreach ($responses as $response)
            @php
                $question = $questions->get($response['question_id']);
            @endphp
            @continue($question === null)
            @php
                $selectionType = in_array($question->type, ['mcq', 'image_selection', 'audio_selection'], true);
                $submittedAnswer = $selectionType
                    ? $question->options
                        ->whereIn('id', (array) $response['answer'])
                        ->map(fn ($option) => $option->label_marathi ?: $option->label)
                        ->implode(', ')
                    : (is_array($response['answer'])
                        ? json_encode($response['answer'], JSON_UNESCAPED_UNICODE)
                        : $response['answer']);
                $correctAnswer = match ($question->type) {
                    'mcq', 'image_selection', 'audio_selection' => $question->options
                        ->whereIn('id', (array) $response['correct_answer'])
                        ->map(fn ($option) => $option->label_marathi ?: $option->label)
                        ->implode(', '),
                    'number_input' => data_get($response['correct_answer'], 'value'),
                    'text_input' => implode(', ', data_get($response['correct_answer'], 'accepted', [])),
                    default => json_encode($response['correct_answer'], JSON_UNESCAPED_UNICODE),
                };
            @endphp
            <article class="card border-{{ $response['is_correct'] ? 'success' : 'danger' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-3"><h2 class="h5">{{ $question->prompt_marathi }}</h2><span class="badge text-bg-{{ $response['is_correct'] ? 'success' : 'danger' }}">{{ $response['is_correct'] ? 'बरोबर' : 'पुन्हा प्रयत्न करा' }}</span></div>
                    <p class="text-secondary">{{ $question->prompt }}</p>
                    <div><strong>तुमचे उत्तर:</strong> {{ $submittedAnswer ?: 'उत्तर दिले नाही' }}</div>
                    @unless ($response['is_correct'])
                        <div><strong>योग्य उत्तर:</strong> {{ $correctAnswer }}</div>
                    @endunless
                    @if ($question->explanation_marathi || $question->explanation)<p class="mt-2 mb-0">{{ $question->explanation_marathi ?: $question->explanation }}</p>@endif
                </div>
            </article>
        @endforeach
    </div>
    <div class="mt-4"><a class="btn btn-primary" href="{{ route('practice.index') }}">आणखी सराव</a></div>
</x-layouts.app>
