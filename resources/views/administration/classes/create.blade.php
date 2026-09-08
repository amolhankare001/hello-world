<x-layouts.app title="Create class">
    <h1 class="h2 portal-brand mb-4">नवीन वर्ग</h1>
    <form method="POST" action="{{ route('school-classes.store') }}" class="card portal-card">
        @csrf
        <div class="card-body p-4">
            @include('administration.classes._form')
            <hr class="my-4">
            <h2 class="h5">पहिली तुकडी</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="division_name">English name</label><input class="form-control @error('division_name') is-invalid @enderror" id="division_name" name="division_name" value="{{ old('division_name') }}" required>@error('division_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="division_name_marathi">मराठी नाव</label><input class="form-control @error('division_name_marathi') is-invalid @enderror" id="division_name_marathi" name="division_name_marathi" value="{{ old('division_name_marathi') }}">@error('division_name_marathi')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2 justify-content-end"><a class="btn btn-outline-secondary" href="{{ route('school-classes.index') }}">रद्द करा</a><button class="btn btn-primary" type="submit">वर्ग जतन करा</button></div>
    </form>
</x-layouts.app>
