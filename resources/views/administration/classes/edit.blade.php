<x-layouts.app title="Edit class">
    <h1 class="h2 portal-brand mb-4">वर्ग संपादित करा</h1>
    <form method="POST" action="{{ route('school-classes.update', $schoolClass) }}" class="card portal-card mb-4">
        @csrf
        @method('PUT')
        <div class="card-body p-4">@include('administration.classes._form')</div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">बदल जतन करा</button></div>
    </form>
    <section class="card portal-card">
        <div class="card-body p-4">
            <h2 class="h5">तुकड्या</h2>
            <div class="vstack gap-3 mb-4">
                @foreach ($schoolClass->divisions as $division)
                    <form method="POST" action="{{ route('school-classes.divisions.update', [$schoolClass, $division]) }}" class="row g-2 align-items-end border-bottom pb-3">
                        @csrf
                        @method('PUT')
                        <div class="col-md-4"><label class="form-label" for="division-name-{{ $division->id }}">English name</label><input class="form-control" id="division-name-{{ $division->id }}" name="name" value="{{ $division->name }}" required></div>
                        <div class="col-md-4"><label class="form-label" for="division-marathi-{{ $division->id }}">मराठी नाव</label><input class="form-control" id="division-marathi-{{ $division->id }}" name="name_marathi" value="{{ $division->name_marathi }}"></div>
                        <div class="col-md-2"><input name="is_active" type="hidden" value="0"><div class="form-check mb-2"><input class="form-check-input" id="division-active-{{ $division->id }}" name="is_active" type="checkbox" value="1" @checked($division->is_active)><label class="form-check-label" for="division-active-{{ $division->id }}">सक्रिय</label></div></div>
                        <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100" type="submit">जतन करा</button></div>
                    </form>
                @endforeach
            </div>
            <form method="POST" action="{{ route('school-classes.divisions.store', $schoolClass) }}" class="row g-3">
                @csrf
                <input name="is_active" type="hidden" value="1">
                <div class="col-md-5"><label class="form-label" for="name">English name</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-5"><label class="form-label" for="division_name_marathi">मराठी नाव</label><input class="form-control @error('name_marathi') is-invalid @enderror" id="division_name_marathi" name="name_marathi" value="{{ old('name_marathi') }}">@error('name_marathi')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100" type="submit">जोडा</button></div>
            </form>
        </div>
    </section>
</x-layouts.app>
