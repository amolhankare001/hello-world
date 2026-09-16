<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">English name</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $schoolClass->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="name_marathi">मराठी नाव</label>
        <input class="form-control @error('name_marathi') is-invalid @enderror" id="name_marathi" name="name_marathi" value="{{ old('name_marathi', $schoolClass->name_marathi ?? '') }}">
        @error('name_marathi')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="grade_level">इयत्ता क्रमांक</label>
        <input class="form-control @error('grade_level') is-invalid @enderror" id="grade_level" name="grade_level" type="number" min="1" max="12" value="{{ old('grade_level', $schoolClass->grade_level ?? '') }}" required>
        @error('grade_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8 d-flex align-items-end">
        <input name="is_active" type="hidden" value="0">
        <div class="form-check mb-2">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $schoolClass->is_active ?? true))>
            <label class="form-check-label" for="is_active">वर्ग सक्रिय आहे</label>
        </div>
    </div>
</div>
