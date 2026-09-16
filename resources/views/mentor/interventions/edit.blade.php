<x-layouts.app :title="'Intervention - '.$intervention->student->user->name">
    <header class="mb-4">
        <a class="small text-decoration-none" href="{{ route('mentor.students.show', $intervention->student) }}">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>विद्यार्थी प्रगती
        </a>
        <h1 class="h2 portal-brand mt-2 mb-1">हस्तक्षेप प्रगती</h1>
        <p class="text-secondary mb-0">{{ $intervention->student->user->name }}{{ $intervention->skill ? ' · '.$intervention->skill->name_marathi : '' }}</p>
    </header>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('mentor.interventions.update', $intervention) }}">
        @csrf
        @method('PUT')
        <div class="card portal-card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <label class="form-label" for="title">योजनेचे नाव</label>
                        <input class="form-control" id="title" name="title" value="{{ old('title', $intervention->title) }}" required>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label" for="status">स्थिती</label>
                        <select class="form-select" id="status" name="status" required>
                            @foreach (['planned' => 'नियोजित', 'active' => 'सक्रिय', 'completed' => 'पूर्ण', 'cancelled' => 'रद्द'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $intervention->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="reason">कारण</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required>{{ old('reason', $intervention->reason) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="plan">कृती योजना</label>
                        <textarea class="form-control" id="plan" name="plan" rows="5" required>{{ old('plan', $intervention->plan) }}</textarea>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="starts_on">सुरू दिनांक</label>
                        <input class="form-control" id="starts_on" name="starts_on" type="date" value="{{ old('starts_on', $intervention->starts_on?->toDateString()) }}" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="target_completion_on">लक्ष्य दिनांक</label>
                        <input class="form-control" id="target_completion_on" name="target_completion_on" type="date" value="{{ old('target_completion_on', $intervention->target_completion_on?->toDateString()) }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="completed_on">पूर्ण दिनांक</label>
                        <input class="form-control" id="completed_on" name="completed_on" type="date" value="{{ old('completed_on', $intervention->completed_on?->toDateString()) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="outcome">परिणाम</label>
                        <textarea class="form-control" id="outcome" name="outcome" rows="3">{{ old('outcome', $intervention->outcome) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        @if ($intervention->activities->isNotEmpty())
            <h2 class="h4 mb-3">योजनेतील कृती</h2>
            @foreach ($intervention->activities as $index => $activity)
                <div class="card portal-card mb-3">
                    <div class="card-body">
                        <input type="hidden" name="activities[{{ $index }}][id]" value="{{ $activity->id }}">
                        <h3 class="h6">{{ $activity->title }}</h3>
                        <p class="small text-secondary">{{ $activity->instructions }}</p>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="activity_completed_{{ $index }}">पूर्ण दिनांक</label>
                                <input class="form-control" id="activity_completed_{{ $index }}" name="activities[{{ $index }}][completed_on]" type="date" value="{{ old("activities.$index.completed_on", $activity->completed_on?->toDateString()) }}">
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label" for="activity_notes_{{ $index }}">मार्गदर्शक नोंद</label>
                                <input class="form-control" id="activity_notes_{{ $index }}" name="activities[{{ $index }}][mentor_notes]" value="{{ old("activities.$index.mentor_notes", $activity->mentor_notes) }}">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        <button class="btn btn-primary" type="submit">प्रगती जतन करा</button>
    </form>
</x-layouts.app>
