<x-layouts.app title="Create student">
    <h1 class="h2 portal-brand mb-4">नवीन विद्यार्थी</h1>
    @if ($divisions->isEmpty())
        <div class="alert alert-warning">विद्यार्थी जोडण्यापूर्वी किमान एक वर्ग आणि तुकडी तयार करा.</div>
    @else
        <form method="POST" action="{{ route('students.store') }}" class="card portal-card">
            @csrf
            <div class="card-body p-4">
                @include('administration.students._form')
                <hr class="my-4">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="division_id">वर्ग आणि तुकडी</label><select class="form-select @error('division_id') is-invalid @enderror" id="division_id" name="division_id" required><option value="">निवडा</option>@foreach ($divisions as $division)<option value="{{ $division->id }}" @selected((string) old('division_id') === (string) $division->id)>{{ $division->schoolClass->name_marathi ?: $division->schoolClass->name }} – {{ $division->name_marathi ?: $division->name }}</option>@endforeach</select>@error('division_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label" for="roll_number">हजेरी क्रमांक</label><input class="form-control @error('roll_number') is-invalid @enderror" id="roll_number" name="roll_number" value="{{ old('roll_number') }}">@error('roll_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label" for="mentor_id">मार्गदर्शक</label><select class="form-select @error('mentor_id') is-invalid @enderror" id="mentor_id" name="mentor_id"><option value="">नंतर नियुक्त करा</option>@foreach ($mentors as $mentor)<option value="{{ $mentor->id }}" @selected((string) old('mentor_id') === (string) $mentor->id)>{{ $mentor->user->name }}</option>@endforeach</select>@error('mentor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2 justify-content-end"><a class="btn btn-outline-secondary" href="{{ route('students.index') }}">रद्द करा</a><button class="btn btn-primary" type="submit">विद्यार्थी जतन करा</button></div>
        </form>
    @endif
</x-layouts.app>
