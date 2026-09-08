@php
    $questionTypes = [
        'mcq' => 'MCQ / बहुपर्यायी',
        'image_selection' => 'Image selection / चित्र निवड',
        'number_input' => 'Number input / संख्या',
        'text_input' => 'Text input / मजकूर',
        'drag_drop' => 'Drag and drop',
        'ordering' => 'Ordering / क्रम',
        'matching' => 'Matching / जुळवा',
        'audio_selection' => 'Audio selection / ध्वनी निवड',
        'interactive_manipulation' => 'Interactive manipulation',
    ];
    $correctAnswerText = match ($question?->type) {
        'number_input' => data_get($question?->correct_answer, 'value', ''),
        'text_input' => implode("\n", data_get($question?->correct_answer, 'accepted', [])),
        'mcq', 'image_selection', 'audio_selection' => '',
        default => $question ? json_encode($question->correct_answer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '',
    };
@endphp
<div class="row g-3">
    <div class="col-md-4"><label class="form-label" for="type">प्रश्न प्रकार / Type</label><select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>@foreach ($questionTypes as $value => $label)<option value="{{ $value }}" @selected(old('type', $question?->type ?? 'mcq') === $value)>{{ $label }}</option>@endforeach</select>@error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-4"><label class="form-label" for="difficulty">कठीणपणा (1–5)</label><input class="form-control @error('difficulty') is-invalid @enderror" id="difficulty" name="difficulty" type="number" min="1" max="5" value="{{ old('difficulty', $question?->difficulty ?? 1) }}" required>@error('difficulty')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-4"><label class="form-label" for="marks">गुण / Marks</label><input class="form-control @error('marks') is-invalid @enderror" id="marks" name="marks" type="number" min="0.01" step="0.01" value="{{ old('marks', $question?->marks ?? 1) }}" required>@error('marks')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="prompt_marathi">मराठी प्रश्न</label><textarea class="form-control @error('prompt_marathi') is-invalid @enderror" id="prompt_marathi" name="prompt_marathi" rows="3" required>{{ old('prompt_marathi', $question?->prompt_marathi) }}</textarea>@error('prompt_marathi')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="prompt">English question</label><textarea class="form-control @error('prompt') is-invalid @enderror" id="prompt" name="prompt" rows="3" required>{{ old('prompt', $question?->prompt) }}</textarea>@error('prompt')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="options">पर्याय / Options</label><textarea class="form-control @error('options') is-invalid @enderror" id="options" name="options" rows="5">{{ old('options', $optionsText) }}</textarea><div class="form-text">प्रत्येक ओळीत English | मराठी. योग्य पर्यायापूर्वी * लावा.</div>@error('options')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="correct_answer">योग्य उत्तर / Correct answer</label><textarea class="form-control @error('correct_answer') is-invalid @enderror" id="correct_answer" name="correct_answer" rows="5">{{ old('correct_answer', $correctAnswerText) }}</textarea><div class="form-text">Number: one value. Text: accepted answers, one per line. Interactive types: JSON.</div>@error('correct_answer')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="explanation_marathi">मराठी स्पष्टीकरण</label><textarea class="form-control" id="explanation_marathi" name="explanation_marathi" rows="3">{{ old('explanation_marathi', $question?->explanation_marathi) }}</textarea></div>
    <div class="col-md-6"><label class="form-label" for="explanation">English explanation</label><textarea class="form-control" id="explanation" name="explanation" rows="3">{{ old('explanation', $question?->explanation) }}</textarea></div>
    <div class="col-md-8"><label class="form-label" for="error_type_id">चूक वर्गीकरण / Error type</label><select class="form-select @error('error_type_id') is-invalid @enderror" id="error_type_id" name="error_type_id"><option value="">वर्गीकरण नाही</option>@foreach ($errorTypes as $errorType)<option value="{{ $errorType->id }}" @selected((string) old('error_type_id', $question?->error_type_id) === (string) $errorType->id)>{{ $errorType->name_marathi }} / {{ $errorType->name }}</option>@endforeach</select>@error('error_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-4 form-check align-self-end mb-2"><input name="is_active" type="hidden" value="0"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $question?->is_active ?? true))><label class="form-check-label" for="is_active">सक्रिय प्रश्न</label></div>
</div>
