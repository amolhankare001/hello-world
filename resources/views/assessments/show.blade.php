<x-layouts.app title="Complete assessment">
    <div class="card portal-card mb-4"><div class="card-body p-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div><span class="badge text-bg-primary mb-2">{{ str_replace('_', ' ', $attempt->test->type) }}</span><h1 class="h2 portal-brand mb-1">{{ $attempt->test->title_marathi }}</h1><p class="text-secondary mb-0">{{ $attempt->test->title }}</p></div><div class="text-md-end"><strong>{{ $questions->count() }} प्रश्न</strong><div class="small text-secondary">समाप्ती: {{ $attempt->expires_at?->format('H:i') }}</div></div></div></div></div>
    <form method="POST" action="{{ route('assessments.attempts.submit', $attempt) }}">
        @csrf
        <div class="vstack gap-4">
            @foreach ($questions as $question)
                <fieldset class="card portal-card">
                    <div class="card-body p-4">
                        <legend class="h5"><span class="badge rounded-pill text-bg-light me-2">{{ $loop->iteration }}</span>{{ $question->prompt_marathi }}</legend>
                        <p class="text-secondary">{{ $question->prompt }}</p>
                        @if ($question->type === 'mcq')
                            @foreach ($question->options as $option)
                                <div class="form-check border rounded p-3 ps-5 mb-2"><input class="form-check-input" id="answer-{{ $question->id }}-{{ $option->id }}" name="answers[{{ $question->id }}]" type="radio" value="{{ $option->id }}"><label class="form-check-label w-100" for="answer-{{ $question->id }}-{{ $option->id }}">{{ $option->label_marathi ?: $option->label }} <span class="text-secondary">/ {{ $option->label }}</span></label></div>
                            @endforeach
                        @elseif (in_array($question->type, ['image_selection', 'audio_selection'], true))
                            @foreach ($question->options as $option)
                                <div class="form-check border rounded p-3 ps-5 mb-2"><input class="form-check-input" id="answer-{{ $question->id }}-{{ $option->id }}" name="answers[{{ $question->id }}][]" type="checkbox" value="{{ $option->id }}"><label class="form-check-label w-100" for="answer-{{ $question->id }}-{{ $option->id }}">{{ $option->label_marathi ?: $option->label }} <span class="text-secondary">/ {{ $option->label }}</span></label></div>
                            @endforeach
                        @elseif ($question->type === 'number_input')
                            <label class="form-label" for="answer-{{ $question->id }}">तुमचे उत्तर</label><input class="form-control form-control-lg" id="answer-{{ $question->id }}" name="answers[{{ $question->id }}]" type="number" step="any">
                        @elseif ($question->type === 'text_input')
                            <label class="form-label" for="answer-{{ $question->id }}">तुमचे उत्तर</label><input class="form-control form-control-lg" id="answer-{{ $question->id }}" name="answers[{{ $question->id }}]" type="text">
                        @else
                            <label class="form-label" for="answer-{{ $question->id }}">उत्तर डेटा / Response JSON</label><textarea class="form-control" id="answer-{{ $question->id }}" name="answers[{{ $question->id }}]" rows="4"></textarea>
                        @endif
                    </div>
                </fieldset>
            @endforeach
        </div>
        @error('answers')<div class="alert alert-danger mt-3">{{ $message }}</div>@enderror
        <div class="text-end mt-4"><button class="btn btn-primary btn-lg" type="submit">चाचणी जमा करा</button></div>
    </form>
</x-layouts.app>
