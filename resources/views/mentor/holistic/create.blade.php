<x-layouts.app :title="$student->user->name.' holistic observation'">
    <header class="mb-4">
        <a class="small text-decoration-none" href="{{ route('mentor.students.show', $student) }}">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>विद्यार्थी प्रगती
        </a>
        <h1 class="h2 portal-brand mt-2 mb-1">समग्र निरीक्षण</h1>
        <p class="text-secondary mb-0">{{ $student->user->name }} · {{ $academicYear->name }}</p>
    </header>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>कृपया माहिती तपासा.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('mentor.students.holistic-observations.store', $student) }}">
        @csrf
        <section class="card portal-card mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h4 mb-1">मार्गदर्शक नोंद</h2>
                <p class="text-secondary mb-0">ही निरीक्षणे शैक्षणिक गुणांपासून स्वतंत्र ठेवली जातील.</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="observed_on">निरीक्षण दिनांक</label>
                        <input class="form-control" id="observed_on" name="observed_on" type="date" value="{{ old('observed_on', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="category">प्रकार</label>
                        <select class="form-select" id="category" name="category" required>
                            @foreach ([
                                'academic' => 'शैक्षणिक',
                                'learning' => 'अध्ययन वर्तन',
                                'social' => 'सामाजिक',
                                'personal' => 'वैयक्तिक',
                                'digital' => 'डिजिटल अध्ययन',
                                'general' => 'सर्वसाधारण',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', 'general') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="holistic_domain_id">मुख्य क्षेत्र</label>
                        <select class="form-select" id="holistic_domain_id" name="holistic_domain_id">
                            <option value="">सर्व क्षेत्रे</option>
                            @foreach ($domains as $domain)
                                <option value="{{ $domain->id }}" @selected((string) old('holistic_domain_id') === (string) $domain->id)>{{ $domain->name_marathi }}</option>
                            @endforeach
                        </select>
                    </div>
                    @foreach ([
                        'observation' => ['निरीक्षण', 4, true],
                        'strengths' => ['बलस्थाने', 3, false],
                        'areas_for_improvement' => ['सुधारणेची क्षेत्रे', 3, false],
                        'recommended_intervention' => ['सुचवलेला हस्तक्षेप', 3, false],
                        'next_learning_goal' => ['पुढील अध्ययन ध्येय', 3, false],
                    ] as $field => [$label, $rows, $required])
                        <div class="col-12">
                            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                            <textarea class="form-control" id="{{ $field }}" name="{{ $field }}" rows="{{ $rows }}" @required($required)>{{ old($field) }}</textarea>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="card portal-card mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h4 mb-1">समग्र निर्देशक रेटिंग</h2>
                <p class="text-secondary mb-0">१ सुरुवात · २ विकसनशील · ३ प्रगतीशील · ४ निपुण · ५ प्रगत</p>
            </div>
            <div class="card-body">
                @php($ratingIndex = 0)
                @foreach ($domains as $domain)
                    <fieldset class="mb-4">
                        <legend class="h5">{{ $domain->name_marathi }}</legend>
                        <div class="row g-3">
                            @foreach ($domain->indicators as $indicator)
                                <div class="col-12 col-lg-6">
                                    <div class="border rounded-4 p-3 h-100">
                                        <input type="hidden" name="ratings[{{ $ratingIndex }}][indicator_id]" value="{{ $indicator->id }}">
                                        <label class="form-label fw-semibold" for="rating_{{ $indicator->id }}">{{ $indicator->name_marathi }}</label>
                                        <select class="form-select mb-2" id="rating_{{ $indicator->id }}" name="ratings[{{ $ratingIndex }}][rating]" required>
                                            @foreach ($indicator->rating_scale as $value => $rating)
                                                <option value="{{ $value }}" @selected((string) old("ratings.{$ratingIndex}.rating", '3') === (string) $value)>
                                                    {{ $value }} — {{ $rating['mr'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label class="visually-hidden" for="rating_notes_{{ $indicator->id }}">{{ $indicator->name_marathi }} नोंद</label>
                                        <input class="form-control" id="rating_notes_{{ $indicator->id }}" name="ratings[{{ $ratingIndex }}][notes]" value="{{ old("ratings.{$ratingIndex}.notes") }}" placeholder="ऐच्छिक पुरावा किंवा उदाहरण">
                                    </div>
                                </div>
                                @php($ratingIndex++)
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>
        </section>

        <button class="btn btn-primary btn-lg" type="submit">निरीक्षण जतन करा</button>
    </form>
</x-layouts.app>
