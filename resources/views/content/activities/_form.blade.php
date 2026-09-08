@php($editingActivity = $activity ?? null)
<div class="row g-3">
    @if (auth()->user()->hasRole(\App\Enums\RoleCode::SuperAdmin))
        <div class="col-md-4">
            <label class="form-label" for="school_id">व्याप्ती / Scope</label>
            <select class="form-select @error('school_id') is-invalid @enderror" id="school_id" name="school_id">
                <option value="">सर्व शाळांसाठी / Global</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" @selected((string) old('school_id', $editingActivity?->school_id) === (string) $school->id)>{{ $school->name_marathi }} / {{ $school->name }}</option>
                @endforeach
            </select>
            @error('school_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @else
        <div class="col-md-4"><label class="form-label">शाळा / School</label><input class="form-control" value="{{ $schools->first()?->name_marathi }}" disabled></div>
    @endif
    <div class="col-md-4">
        <label class="form-label" for="skill_id">कौशल्य / Skill</label>
        <select class="form-select @error('skill_id') is-invalid @enderror" id="skill_id" name="skill_id" required>
            <option value="">कौशल्य निवडा</option>
            @foreach ($subjects as $subject)
                <optgroup label="{{ $subject->name_marathi }} / {{ $subject->name }}">
                    @foreach ($subject->skills as $skill)
                        <option value="{{ $skill->id }}" @selected((string) old('skill_id', $editingActivity?->skill_id) === (string) $skill->id)>{{ $skill->name_marathi }} / {{ $skill->name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('skill_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="skill_level_id">पातळी / Level</label>
        <select class="form-select @error('skill_level_id') is-invalid @enderror" id="skill_level_id" name="skill_level_id">
            <option value="">सर्व पातळ्या</option>
            @foreach ($subjects as $subject)
                @foreach ($subject->skills as $skill)
                    @foreach ($skill->levels as $level)
                        <option value="{{ $level->id }}" @selected((string) old('skill_level_id', $editingActivity?->skill_level_id) === (string) $level->id)>{{ $skill->name_marathi }} — {{ $level->name_marathi }} ({{ $level->level }})</option>
                    @endforeach
                @endforeach
            @endforeach
        </select>
        @error('skill_level_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4"><label class="form-label" for="code">Activity code</label><input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $editingActivity?->code) }}" required>@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-4"><label class="form-label" for="type">प्रकार / Type</label><select class="form-select" id="type" name="type" required>@foreach (['learn' => 'Learn / शिका', 'practice' => 'Practice / सराव', 'game' => 'Game / खेळ', 'simulation' => 'Simulation / अनुकरण', 'assessment' => 'Assessment / मूल्यमापन'] as $value => $label)<option value="{{ $value }}" @selected(old('type', $editingActivity?->type ?? 'learn') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label" for="status">स्थिती / Status</label><select class="form-select" id="status" name="status" required>@foreach (['draft' => 'Draft / मसुदा', 'published' => 'Published / प्रकाशित', 'archived' => 'Archived / संग्रहित'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $editingActivity?->status ?? 'draft') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label" for="title">English title</label><input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $editingActivity?->title) }}" required>@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="title_marathi">मराठी शीर्षक</label><input class="form-control @error('title_marathi') is-invalid @enderror" id="title_marathi" name="title_marathi" value="{{ old('title_marathi', $editingActivity?->title_marathi) }}" required>@error('title_marathi')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="instructions">English instructions</label><textarea class="form-control" id="instructions" name="instructions" rows="3">{{ old('instructions', $editingActivity?->instructions) }}</textarea></div>
    <div class="col-md-6"><label class="form-label" for="instructions_marathi">मराठी सूचना</label><textarea class="form-control" id="instructions_marathi" name="instructions_marathi" rows="3">{{ old('instructions_marathi', $editingActivity?->instructions_marathi) }}</textarea></div>
    <div class="col-md-6"><label class="form-label" for="content_body">English learning content</label><textarea class="form-control" id="content_body" name="content_body" rows="5">{{ old('content_body', data_get($editingActivity?->content, 'body')) }}</textarea></div>
    <div class="col-md-6"><label class="form-label" for="content_body_marathi">मराठी अध्ययन सामग्री</label><textarea class="form-control" id="content_body_marathi" name="content_body_marathi" rows="5">{{ old('content_body_marathi', data_get($editingActivity?->content, 'body_marathi')) }}</textarea></div>
    <div class="col-12"><label class="form-label" for="examples">उदाहरणे / Examples <span class="text-secondary">(one per line)</span></label><textarea class="form-control" id="examples" name="examples" rows="3">{{ old('examples', implode("\n", data_get($editingActivity?->content, 'examples', []))) }}</textarea></div>
    <div class="col-md-4"><label class="form-label" for="difficulty">कठीणपणा / Difficulty (1–5)</label><input class="form-control" id="difficulty" name="difficulty" type="number" min="1" max="5" value="{{ old('difficulty', $editingActivity?->difficulty ?? 1) }}" required></div>
    <div class="col-md-4"><label class="form-label" for="estimated_minutes">अंदाजित मिनिटे</label><input class="form-control" id="estimated_minutes" name="estimated_minutes" type="number" min="1" max="600" value="{{ old('estimated_minutes', $editingActivity?->estimated_minutes ?? 10) }}"></div>
    <div class="col-md-4"><label class="form-label" for="max_score">कमाल गुण / Max score</label><input class="form-control" id="max_score" name="max_score" type="number" min="0" value="{{ old('max_score', $editingActivity?->max_score ?? 10) }}" required></div>
</div>
