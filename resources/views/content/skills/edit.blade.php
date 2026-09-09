<x-layouts.app title="Edit skill">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h2 portal-brand mb-1">{{ $skill->name_marathi }}</h1><p class="text-secondary mb-0">{{ $subject->name_marathi }} · {{ $skill->name }}</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('subjects.edit', $subject) }}">विषयाकडे परत</a>
    </div>
    <form method="POST" action="{{ route('subjects.skills.update', [$subject, $skill]) }}" class="card portal-card mb-4">
        @csrf
        @method('PUT')
        <div class="card-body p-4">@include('content.skills._form')</div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">कौशल्य जतन करा</button></div>
    </form>
    <section class="card portal-card">
        <div class="card-body p-4">
            <h2 class="h4 mb-3">प्रावीण्य पातळ्या / Mastery levels</h2>
            @foreach ($skill->levels as $level)
                <form method="POST" action="{{ route('subjects.skills.levels.update', [$subject, $skill, $level]) }}" class="border rounded p-3 mb-3">
                    @csrf
                    @method('PUT')
                    <div class="row g-2 align-items-end">
                        <div class="col-md-1"><label class="form-label">Level</label><input class="form-control" name="level" type="number" min="1" value="{{ old('level_'.$level->id, $level->level) }}" required></div>
                        <div class="col-md-2"><label class="form-label">English name</label><input class="form-control" name="name" value="{{ $level->name }}" required></div>
                        <div class="col-md-2"><label class="form-label">मराठी नाव</label><input class="form-control" name="name_marathi" value="{{ $level->name_marathi }}" required></div>
                        <div class="col-md-4"><label class="form-label">Learning objective</label><input class="form-control" name="learning_objective" value="{{ $level->learning_objective }}"></div>
                        <div class="col-md-2"><label class="form-label">Mastery %</label><input class="form-control" name="mastery_threshold" type="number" min="0" max="100" step="0.01" value="{{ $level->mastery_threshold }}" required></div>
                        <div class="col-md-1"><button class="btn btn-sm btn-outline-primary w-100" type="submit">जतन</button></div>
                    </div>
                </form>
            @endforeach
            <form method="POST" action="{{ route('subjects.skills.levels.store', [$subject, $skill]) }}" class="bg-light rounded p-3">
                @csrf
                <h3 class="h6">नवीन पातळी</h3>
                <div class="row g-2 align-items-end">
                    <div class="col-md-1"><label class="form-label">Level</label><input class="form-control" name="level" type="number" min="1" value="{{ old('level', $skill->levels->max('level') + 1) }}" required></div>
                    <div class="col-md-2"><label class="form-label">English name</label><input class="form-control" name="name" value="{{ old('name') }}" required></div>
                    <div class="col-md-2"><label class="form-label">मराठी नाव</label><input class="form-control" name="name_marathi" value="{{ old('name_marathi') }}" required></div>
                    <div class="col-md-4"><label class="form-label">Learning objective</label><input class="form-control" name="learning_objective" value="{{ old('learning_objective') }}"></div>
                    <div class="col-md-2"><label class="form-label">Mastery %</label><input class="form-control" name="mastery_threshold" type="number" min="0" max="100" step="0.01" value="{{ old('mastery_threshold', 80) }}" required></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">जोडा</button></div>
                </div>
            </form>
        </div>
    </section>
</x-layouts.app>
