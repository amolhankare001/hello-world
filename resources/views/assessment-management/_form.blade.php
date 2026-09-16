@php
    $editingTest = $test ?? null;
    $selectedIds = collect(old('question_ids', $selectedQuestionIds ?? []))->map(fn ($id) => (int) $id);
@endphp
<div class="row g-3">
    @if (auth()->user()->hasRole(\App\Enums\RoleCode::SuperAdmin))
        <div class="col-md-4">
            <label class="form-label" for="school_id">व्याप्ती / Scope</label>
            <select class="form-select @error('school_id') is-invalid @enderror" id="school_id" name="school_id">
                <option value="">सर्व शाळांसाठी / Global</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" @selected((string) old('school_id', $editingTest?->school_id) === (string) $school->id)>{{ $school->name_marathi }} / {{ $school->name }}</option>
                @endforeach
            </select>
            @error('school_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @else
        <div class="col-md-4"><label class="form-label">शाळा / School</label><input class="form-control" value="{{ $schools->first()?->name_marathi }}" disabled></div>
    @endif
    <div class="col-md-4">
        <label class="form-label" for="academic_year_id">शैक्षणिक वर्ष</label>
        <select class="form-select @error('academic_year_id') is-invalid @enderror" id="academic_year_id" name="academic_year_id">
            <option value="">सध्याचे कोणतेही वर्ष</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $editingTest?->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
            @endforeach
        </select>
        @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="school_class_id">वर्ग / Class</label>
        <select class="form-select @error('school_class_id') is-invalid @enderror" id="school_class_id" name="school_class_id">
            <option value="">सर्व वर्ग</option>
            @foreach ($schoolClasses as $schoolClass)
                <option value="{{ $schoolClass->id }}" @selected((string) old('school_class_id', $editingTest?->school_class_id) === (string) $schoolClass->id)>{{ $schoolClass->name_marathi }} / {{ $schoolClass->name }}</option>
            @endforeach
        </select>
        @error('school_class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="subject_id">विषय / Subject</label>
        <select class="form-select @error('subject_id') is-invalid @enderror" id="subject_id" name="subject_id" required>
            <option value="">विषय निवडा</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected((string) old('subject_id', $editingTest?->subject_id) === (string) $subject->id)>{{ $subject->name_marathi }} / {{ $subject->name }}</option>
            @endforeach
        </select>
        @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4"><label class="form-label" for="code">Assessment code</label><input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $editingTest?->code) }}" required>@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-4">
        <label class="form-label" for="type">चाचणी प्रकार / Type</label>
        <select class="form-select" id="type" name="type" required>
            @foreach (['pre_test' => 'Pre-test / पूर्व चाचणी', 'post_test' => 'Post-test / उत्तर चाचणी', 'reassessment' => 'Reassessment / पुनर्मूल्यांकन'] as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $editingTest?->type ?? 'pre_test') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6"><label class="form-label" for="title">English title</label><input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $editingTest?->title) }}" required>@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="title_marathi">मराठी शीर्षक</label><input class="form-control @error('title_marathi') is-invalid @enderror" id="title_marathi" name="title_marathi" value="{{ old('title_marathi', $editingTest?->title_marathi) }}" required>@error('title_marathi')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="instructions">English instructions</label><textarea class="form-control" id="instructions" name="instructions" rows="3">{{ old('instructions', $editingTest?->instructions) }}</textarea></div>
    <div class="col-md-6"><label class="form-label" for="instructions_marathi">मराठी सूचना</label><textarea class="form-control" id="instructions_marathi" name="instructions_marathi" rows="3">{{ old('instructions_marathi', $editingTest?->instructions_marathi) }}</textarea></div>
    <div class="col-md-3"><label class="form-label" for="duration_minutes">कालावधी (मिनिटे)</label><input class="form-control" id="duration_minutes" name="duration_minutes" type="number" min="1" max="240" value="{{ old('duration_minutes', $editingTest?->duration_minutes ?? 20) }}" required></div>
    <div class="col-md-3"><label class="form-label" for="difficulty">कठीणपणा (1–5)</label><input class="form-control" id="difficulty" name="difficulty" type="number" min="1" max="5" value="{{ old('difficulty', $editingTest?->difficulty ?? 1) }}" required></div>
    <div class="col-md-3"><label class="form-label" for="question_count">प्रश्न संख्या</label><input class="form-control @error('question_count') is-invalid @enderror" id="question_count" name="question_count" type="number" min="1" max="100" value="{{ old('question_count', $editingTest?->question_count ?? 10) }}" required>@error('question_count')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-3"><label class="form-label" for="max_attempts">कमाल प्रयत्न</label><input class="form-control" id="max_attempts" name="max_attempts" type="number" min="1" max="10" value="{{ old('max_attempts', $editingTest?->max_attempts ?? 1) }}" required></div>
    <div class="col-md-3"><label class="form-label" for="passing_score">उत्तीर्ण टक्केवारी</label><input class="form-control" id="passing_score" name="passing_score" type="number" min="0" max="100" step="0.01" value="{{ old('passing_score', $editingTest?->passing_score ?? 60) }}"></div>
    <div class="col-md-3"><label class="form-label" for="shuffle_questions">प्रश्न क्रम</label><select class="form-select" id="shuffle_questions" name="shuffle_questions"><option value="0" @selected(! old('shuffle_questions', $editingTest?->shuffle_questions ?? false))>ठरलेला क्रम</option><option value="1" @selected(old('shuffle_questions', $editingTest?->shuffle_questions ?? false))>यादृच्छिक</option></select></div>
    <div class="col-md-3"><label class="form-label" for="status">स्थिती</label><select class="form-select" id="status" name="status">@foreach (['draft' => 'मसुदा', 'published' => 'प्रकाशित', 'archived' => 'संग्रहित'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $editingTest?->status ?? 'draft') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label" for="available_from">उपलब्ध पासून</label><input class="form-control" id="available_from" name="available_from" type="datetime-local" value="{{ old('available_from', $editingTest?->available_from?->format('Y-m-d\TH:i')) }}"></div>
    <div class="col-md-3"><label class="form-label" for="available_until">उपलब्ध पर्यंत</label><input class="form-control" id="available_until" name="available_until" type="datetime-local" value="{{ old('available_until', $editingTest?->available_until?->format('Y-m-d\TH:i')) }}"></div>
</div>

<section class="mt-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-2"><div><h2 class="h5 mb-1">प्रश्न बँक</h2><p class="small text-secondary mb-0">निवडलेल्या विषयातील किमान प्रश्न संख्या इतके प्रश्न निवडा.</p></div><span class="badge text-bg-light">{{ $questions->count() }} उपलब्ध</span></div>
    @error('question_ids')<div class="alert alert-danger">{{ $message }}</div>@enderror
    <div class="border rounded overflow-auto" style="max-height: 32rem">
        @forelse ($questions->groupBy('skill.subject_id') as $subjectId => $subjectQuestions)
            <div class="p-3 bg-light border-bottom"><strong>{{ $subjects->firstWhere('id', $subjectId)?->name_marathi }} / {{ $subjects->firstWhere('id', $subjectId)?->name }}</strong></div>
            @foreach ($subjectQuestions as $question)
                <label class="d-flex gap-3 p-3 border-bottom align-items-start">
                    <input class="form-check-input mt-1" name="question_ids[]" type="checkbox" value="{{ $question->id }}" @checked($selectedIds->contains($question->id))>
                    <span><strong>{{ $question->prompt_marathi }}</strong><span class="d-block text-secondary">{{ $question->prompt }}</span><span class="small text-secondary">{{ $question->skill->name_marathi }} · पातळी {{ $question->difficulty }} · {{ $question->type }}</span></span>
                </label>
            @endforeach
        @empty
            <p class="p-4 text-secondary mb-0">प्रथम उपक्रमामध्ये प्रश्न तयार करा.</p>
        @endforelse
    </div>
</section>
