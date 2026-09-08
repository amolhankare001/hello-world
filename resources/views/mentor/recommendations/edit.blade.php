<x-layouts.app :title="'Modify recommendation - '.$recommendation->student->user->name">
    <header class="mb-4">
        <a class="small text-decoration-none" href="{{ route('mentor.students.show', $recommendation->student) }}">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>विद्यार्थी प्रगती
        </a>
        <h1 class="h2 portal-brand mt-2 mb-1">शिफारस बदला</h1>
        <p class="text-secondary mb-0">{{ $recommendation->student->user->name }} · {{ $recommendation->skill->name_marathi }}</p>
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

    <form method="POST" action="{{ route('mentor.recommendations.update', $recommendation) }}">
        @csrf
        @method('PUT')
        <div class="card portal-card mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="reason_marathi">मराठी कारण</label>
                    <textarea class="form-control" id="reason_marathi" name="reason_marathi" rows="3" required>{{ old('reason_marathi', $recommendation->reason_marathi) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="reason">English reason</label>
                    <textarea class="form-control" id="reason" name="reason" rows="3" required>{{ old('reason', $recommendation->reason) }}</textarea>
                </div>
                <div>
                    <label class="form-label" for="mentor_notes">मार्गदर्शक नोंद</label>
                    <textarea class="form-control" id="mentor_notes" name="mentor_notes" rows="3">{{ old('mentor_notes', $recommendation->mentor_notes) }}</textarea>
                </div>
            </div>
        </div>

        <h2 class="h4 mb-3">अध्ययन मार्ग</h2>
        @foreach ($recommendation->items as $index => $item)
            <fieldset class="card portal-card mb-3">
                <div class="card-body">
                    <legend class="h6">पायरी {{ $index + 1 }}</legend>
                    <input type="hidden" name="items[{{ $index }}][item_type]" value="{{ $item->item_type }}">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="item_title_marathi_{{ $index }}">मराठी नाव</label>
                            <input class="form-control" id="item_title_marathi_{{ $index }}" name="items[{{ $index }}][title_marathi]" value="{{ old("items.$index.title_marathi", $item->title_marathi) }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="item_title_{{ $index }}">English title</label>
                            <input class="form-control" id="item_title_{{ $index }}" name="items[{{ $index }}][title]" value="{{ old("items.$index.title", $item->title) }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="item_instructions_marathi_{{ $index }}">मराठी सूचना</label>
                            <textarea class="form-control" id="item_instructions_marathi_{{ $index }}" name="items[{{ $index }}][instructions_marathi]" rows="2">{{ old("items.$index.instructions_marathi", $item->instructions_marathi) }}</textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="item_instructions_{{ $index }}">English instructions</label>
                            <textarea class="form-control" id="item_instructions_{{ $index }}" name="items[{{ $index }}][instructions]" rows="2">{{ old("items.$index.instructions", $item->instructions) }}</textarea>
                        </div>
                    </div>
                </div>
            </fieldset>
        @endforeach
        <button class="btn btn-primary" type="submit">बदल जतन करा</button>
    </form>
</x-layouts.app>
