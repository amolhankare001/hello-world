<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="code">विषय कोड / Subject code</label>
        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $subject->code ?? '') }}" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="name">English name</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $subject->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="name_marathi">मराठी नाव</label>
        <input class="form-control @error('name_marathi') is-invalid @enderror" id="name_marathi" name="name_marathi" value="{{ old('name_marathi', $subject->name_marathi ?? '') }}" required>
        @error('name_marathi')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="sort_order">क्रम / Order</label>
        <input class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $subject->sort_order ?? 0) }}" required>
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-9">
        <label class="form-label" for="description">वर्णन / Description</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $subject->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <input name="is_active" type="hidden" value="0">
        <div class="form-check">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $subject->is_active ?? true))>
            <label class="form-check-label" for="is_active">विषय सक्रिय आहे / Subject is active</label>
        </div>
    </div>
</div>
