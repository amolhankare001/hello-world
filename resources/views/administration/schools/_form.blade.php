<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="code">शाळा कोड</label>
        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $school->code ?? '') }}" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label class="form-label" for="name">English name</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $school->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label class="form-label" for="name_marathi">मराठी नाव</label>
        <input class="form-control @error('name_marathi') is-invalid @enderror" id="name_marathi" name="name_marathi" value="{{ old('name_marathi', $school->name_marathi ?? '') }}">
        @error('name_marathi')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="timezone">Timezone</label>
        <input class="form-control @error('timezone') is-invalid @enderror" id="timezone" name="timezone" value="{{ old('timezone', $school->timezone ?? 'Asia/Kolkata') }}" required>
        @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email">ईमेल</label>
        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $school->email ?? '') }}">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone">फोन</label>
        <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $school->phone ?? '') }}">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="address">पत्ता</label>
        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $school->address ?? '') }}</textarea>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <input name="is_active" type="hidden" value="0">
        <div class="form-check">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $school->is_active ?? true))>
            <label class="form-check-label" for="is_active">शाळा सक्रिय आहे</label>
        </div>
    </div>
</div>
