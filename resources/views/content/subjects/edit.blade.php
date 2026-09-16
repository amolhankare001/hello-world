<x-layouts.app title="Edit subject">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 portal-brand mb-0">विषय संपादित करा</h1>
        <a class="btn btn-outline-secondary" href="{{ route('subjects.index') }}">विषय सूची</a>
    </div>
    <form method="POST" action="{{ route('subjects.update', $subject) }}" class="card portal-card mb-4">
        @csrf
        @method('PUT')
        <div class="card-body p-4">@include('content.subjects._form')</div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">बदल जतन करा</button></div>
    </form>
    <section class="card portal-card">
        <div class="card-body p-4">
            <h2 class="h4 mb-3">कौशल्य अध्ययन मार्ग / Skill learning path</h2>
            <div class="list-group mb-4">
                @forelse ($subject->skills as $skill)
                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ route('subjects.skills.edit', [$subject, $skill]) }}">
                        <span><strong>{{ $skill->name_marathi }}</strong><span class="text-secondary ms-2">{{ $skill->name }}</span><span class="badge text-bg-light ms-2">{{ $skill->code }}</span></span>
                        <span class="small text-secondary">क्रम {{ $skill->sort_order }} · {{ $skill->levels_count }} levels · {{ $skill->activities_count }} activities</span>
                    </a>
                @empty
                    <div class="text-secondary">अद्याप कौशल्ये नाहीत.</div>
                @endforelse
            </div>
            <h3 class="h5">नवीन कौशल्य जोडा</h3>
            <form method="POST" action="{{ route('subjects.skills.store', $subject) }}" class="row g-3">
                @csrf
                <div class="col-md-3"><label class="form-label" for="skill_code">Code</label><input class="form-control @error('code') is-invalid @enderror" id="skill_code" name="code" value="{{ old('code') }}" required>@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-3"><label class="form-label" for="skill_name">English name</label><input class="form-control" id="skill_name" name="name" value="{{ old('name') }}" required></div>
                <div class="col-md-3"><label class="form-label" for="skill_name_marathi">मराठी नाव</label><input class="form-control" id="skill_name_marathi" name="name_marathi" value="{{ old('name_marathi') }}" required></div>
                <div class="col-md-3"><label class="form-label" for="parent_skill_id">पूर्व कौशल्य / Parent</label><select class="form-select" id="parent_skill_id" name="parent_skill_id"><option value="">मूळ कौशल्य</option>@foreach ($subject->skills as $parentSkill)<option value="{{ $parentSkill->id }}" @selected((string) old('parent_skill_id') === (string) $parentSkill->id)>{{ $parentSkill->name_marathi }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label" for="skill_sort_order">क्रम</label><input class="form-control" id="skill_sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $subject->skills->count() + 1) }}" required></div>
                <div class="col-md-8"><label class="form-label" for="skill_description">वर्णन</label><input class="form-control" id="skill_description" name="description" value="{{ old('description') }}"></div>
                <div class="col-md-2 d-flex align-items-end"><input name="is_active" type="hidden" value="1"><button class="btn btn-outline-primary w-100" type="submit">कौशल्य जोडा</button></div>
            </form>
        </div>
    </section>
</x-layouts.app>
