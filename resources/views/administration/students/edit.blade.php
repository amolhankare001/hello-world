<x-layouts.app title="Edit student">
    <h1 class="h2 portal-brand mb-4">विद्यार्थी संपादित करा</h1>
    <form method="POST" action="{{ route('students.update', $student) }}" class="card portal-card mb-4">
        @csrf
        @method('PUT')
        <div class="card-body p-4">@include('administration.students._form')</div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">बदल जतन करा</button></div>
    </form>
    <section class="card portal-card">
        <div class="card-body p-4">
            <h2 class="h5">मार्गदर्शक नियुक्ती</h2>
            <p class="text-secondary">सध्याचे: {{ $student->mentorAssignments->first()?->mentor->user->name ?: 'नियुक्त नाही' }}</p>
            <form method="POST" action="{{ route('students.mentor-assignment.store', $student) }}" class="row g-3">
                @csrf
                <div class="col-md-9"><label class="form-label" for="mentor_id">नवीन मार्गदर्शक</label><select class="form-select @error('mentor_id') is-invalid @enderror" id="mentor_id" name="mentor_id" required><option value="">निवडा</option>@foreach ($mentors as $mentor)<option value="{{ $mentor->id }}">{{ $mentor->user->name }}</option>@endforeach</select>@error('mentor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-3 d-flex align-items-end"><button class="btn btn-outline-primary w-100" type="submit">नियुक्त करा</button></div>
            </form>
        </div>
    </section>
</x-layouts.app>
