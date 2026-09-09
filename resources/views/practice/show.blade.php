<x-layouts.app title="Complete practice">
    <div class="mb-4"><h1 class="h2 portal-brand mb-1">{{ $attempt->practiceActivity->activity->title_marathi }}</h1><p class="text-secondary mb-0">{{ $attempt->practiceActivity->activity->title }} · पातळी {{ $attempt->difficulty }}</p></div>
    <form method="POST" action="{{ route('practice.attempts.submit', $attempt) }}">
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
        <div class="text-end mt-4"><button class="btn btn-primary btn-lg" type="submit">उत्तरे जमा करा</button></div>
    </form>
</x-layouts.app>
