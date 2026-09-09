<x-layouts.app :title="$student->user->name.' progress'">
    <header class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a class="small text-decoration-none" href="{{ route('mentor.dashboard') }}">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>डॅशबोर्ड
            </a>
            <h1 class="h2 portal-brand mt-2 mb-1">{{ $student->user->name }}</h1>
            <p class="text-secondary mb-0">{{ $student->student_number }} · {{ $academicYear->name }}</p>
        </div>
        <form method="POST" action="{{ route('mentor.recommendations.refresh') }}">
            @csrf
            <button class="btn btn-primary" type="submit">शिफारसी अद्ययावत करा</button>
        </form>
    </header>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>कृपया खालील माहिती तपासा.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="row g-3 mb-4" aria-label="Learning evidence">
        @foreach ([
            ['सराव सत्रे', $evidenceSummary['practice_sessions'], 'bi-pencil-square'],
            ['खेळ सत्रे', $evidenceSummary['game_sessions'], 'bi-controller'],
            ['अनुकरण सत्रे', $evidenceSummary['simulation_sessions'], 'bi-boxes'],
            ['मूल्यांकन उत्तरे', $evidenceSummary['assessment_responses'], 'bi-clipboard-check'],
            ['सरासरी अचूकता', $evidenceSummary['average_accuracy'].'%', 'bi-bullseye'],
            ['शिकण्याची मिनिटे', $evidenceSummary['total_minutes'], 'bi-clock-history'],
        ] as [$label, $value, $icon])
            <div class="col-6 col-lg-2">
                <div class="card portal-card h-100">
                    <div class="card-body">
                        <i class="bi {{ $icon }} portal-brand" aria-hidden="true"></i>
                        <div class="h4 mt-2 mb-0">{{ $value }}</div>
                        <span class="small text-secondary">{{ $label }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="card portal-card mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h4 mb-1">कौशल्य निदान</h2>
            <p class="text-secondary mb-0">प्रभुत्व, अचूकता, सराव, खेळ, अनुकरण, चुका आणि अलीकडील कल स्वतंत्रपणे पाहा.</p>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">कौशल्य</th>
                        <th scope="col">स्थिती</th>
                        <th scope="col">प्रभुत्व</th>
                        <th scope="col">अचूकता</th>
                        <th scope="col">सराव</th>
                        <th scope="col">खेळ</th>
                        <th scope="col">अनुकरण</th>
                        <th scope="col">वारंवार चूक</th>
                        <th scope="col">कल</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($analyses as $analysis)
                        @php
                            $metrics = $analysis['metrics'];
                        @endphp
                        <tr>
                            <th scope="row">
                                {{ $analysis['skill']->name_marathi }}
                                <div class="small fw-normal text-secondary">{{ $analysis['skill']->subject->name_marathi }}</div>
                            </th>
                            <td>
                                <span class="badge {{ match ($analysis['risk_level']) {
                                    'red' => 'text-bg-danger',
                                    'yellow' => 'text-bg-warning',
                                    default => 'text-bg-success',
                                } }}">
                                    {{ strtoupper($analysis['risk_level']) }}
                                </span>
                            </td>
                            <td>{{ $metrics['mastery'] }}%</td>
                            <td>{{ $metrics['accuracy'] }}%</td>
                            <td>{{ $metrics['practice_performance'] }}% <span class="small text-secondary">({{ $metrics['practice_count'] }})</span></td>
                            <td>{{ $metrics['game_performance'] }}% <span class="small text-secondary">({{ $metrics['game_count'] }})</span></td>
                            <td>{{ $metrics['simulation_performance'] }}% <span class="small text-secondary">({{ $metrics['simulation_count'] }})</span></td>
                            <td>
                                @if ($metrics['repeated_error'])
                                    {{ $metrics['repeated_error']['name_marathi'] }}
                                    <span class="badge text-bg-light">{{ $metrics['repeated_errors'] }}</span>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td class="{{ $metrics['recent_trend'] < 0 ? 'text-danger' : ($metrics['recent_trend'] > 0 ? 'text-success' : '') }}">
                                {{ $metrics['recent_trend'] > 0 ? '+' : '' }}{{ $metrics['recent_trend'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card portal-card h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h2 class="h4 mb-0">शिफारस केलेले अध्ययन मार्ग</h2>
                </div>
                <div class="card-body">
                    @forelse ($recommendations as $recommendation)
                        <article class="border rounded-4 p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <span class="badge {{ $recommendation->risk_level === 'red' ? 'text-bg-danger' : 'text-bg-warning' }}">
                                        {{ strtoupper($recommendation->risk_level) }}
                                    </span>
                                    <h3 class="h5 mt-2 mb-1">{{ $recommendation->skill->name_marathi }}</h3>
                                </div>
                                <span class="badge text-bg-light">{{ $recommendation->status }}</span>
                            </div>
                            <p class="text-secondary">{{ $recommendation->reason_marathi }}</p>
                            <ol class="learning-path-list">
                                @foreach ($recommendation->items as $item)
                                    <li class="mb-2">
                                        <strong>{{ $item->title_marathi }}</strong>
                                        @if ($item->instructions_marathi)
                                            <div class="small text-secondary">{{ $item->instructions_marathi }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                            @if (in_array($recommendation->status, ['pending', 'modified'], true))
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <form method="POST" action="{{ route('mentor.recommendations.accept', $recommendation) }}">
                                        @csrf
                                        <button class="btn btn-success" type="submit">स्वीकारा आणि योजना तयार करा</button>
                                    </form>
                                    <a class="btn btn-outline-secondary" href="{{ route('mentor.recommendations.edit', $recommendation) }}">बदला</a>
                                    <form method="POST" action="{{ route('mentor.recommendations.reject', $recommendation) }}">
                                        @csrf
                                        <button class="btn btn-outline-danger" type="submit">नाकारा</button>
                                    </form>
                                </div>
                            @endif
                        </article>
                    @empty
                        <p class="text-secondary mb-0">नवीन शिफारस उपलब्ध नाही.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card portal-card h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h2 class="h4 mb-0">हस्तक्षेप योजना</h2>
                </div>
                <div class="card-body">
                    @forelse ($interventions as $intervention)
                        <article class="border rounded-4 p-3 mb-3">
                            <div class="d-flex justify-content-between gap-2">
                                <h3 class="h6 mb-1">{{ $intervention->title }}</h3>
                                <span class="badge text-bg-primary">{{ $intervention->status }}</span>
                            </div>
                            <p class="small text-secondary mb-2">{{ $intervention->reason }}</p>
                            <span class="small">{{ $intervention->activities->whereNotNull('completed_on')->count() }}/{{ $intervention->activities->count() }} कृती पूर्ण</span>
                            <div class="mt-3">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('mentor.interventions.edit', $intervention) }}">प्रगती अद्ययावत करा</a>
                            </div>
                        </article>
                    @empty
                        <p class="text-secondary">सध्या हस्तक्षेप योजना नाही.</p>
                    @endforelse

                    <details class="mt-3">
                        <summary class="fw-semibold">स्वतःची योजना तयार करा</summary>
                        <form class="mt-3" method="POST" action="{{ route('mentor.students.interventions.store', $student) }}">
                            @csrf
                            <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">
                            <div class="mb-3">
                                <label class="form-label" for="skill_id">कौशल्य</label>
                                <select class="form-select" id="skill_id" name="skill_id">
                                    <option value="">सर्वसाधारण मदत</option>
                                    @foreach ($analyses as $analysis)
                                        <option value="{{ $analysis['skill']->id }}">{{ $analysis['skill']->name_marathi }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="title">योजनेचे नाव</label>
                                <input class="form-control" id="title" name="title" value="{{ old('title') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="reason">कारण</label>
                                <textarea class="form-control" id="reason" name="reason" rows="2" required>{{ old('reason') }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="plan">कृती योजना</label>
                                <textarea class="form-control" id="plan" name="plan" rows="4" required>{{ old('plan') }}</textarea>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label" for="starts_on">सुरू दिनांक</label>
                                    <input class="form-control" id="starts_on" name="starts_on" type="date" value="{{ old('starts_on', now()->toDateString()) }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="target_completion_on">लक्ष्य दिनांक</label>
                                    <input class="form-control" id="target_completion_on" name="target_completion_on" type="date" value="{{ old('target_completion_on', now()->addWeeks(2)->toDateString()) }}">
                                </div>
                            </div>
                            <button class="btn btn-primary" type="submit">योजना तयार करा</button>
                        </form>
                    </details>
                </div>
            </div>
        </div>
    </section>

    <section class="card portal-card mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-column flex-md-row justify-content-between gap-3">
            <div>
                <h2 class="h4 mb-1">समग्र प्रगती</h2>
                <p class="text-secondary mb-0">मार्गदर्शक निरीक्षणे आणि निर्देशक शैक्षणिक पुराव्यापासून स्वतंत्र आहेत.</p>
            </div>
            <a class="btn btn-primary align-self-md-start" href="{{ route('mentor.students.holistic-observations.create', $student) }}">
                निरीक्षण नोंदवा
            </a>
            <a class="btn btn-outline-primary align-self-md-start" href="{{ route('mentor.students.portfolio.index', $student) }}">
                पोर्टफोलिओ
            </a>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-12 col-xl-7">
                    @forelse ($holisticRecords as $domainName => $records)
                        <div class="mb-4">
                            <h3 class="h6">{{ $domainName }}</h3>
                            <div class="row g-2">
                                @foreach ($records as $record)
                                    <div class="col-12 col-md-6">
                                        <div class="border rounded-3 p-3 d-flex justify-content-between gap-3">
                                            <span>{{ $record->indicator->name_marathi }}</span>
                                            <span class="badge text-bg-primary">{{ (int) $record->rating }}/5</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary">अद्याप निर्देशक रेटिंग नोंदवलेले नाही.</p>
                    @endforelse
                </div>
                <div class="col-12 col-xl-5">
                    <h3 class="h6">अलीकडील निरीक्षणे</h3>
                    @forelse ($observations as $observation)
                        <article class="border rounded-4 p-3 mb-3">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="badge text-bg-light">{{ $observation->domain?->name_marathi ?? 'सर्वसाधारण' }}</span>
                                <span class="small text-secondary">{{ $observation->observed_on->format('d-m-Y') }}</span>
                            </div>
                            <p class="mt-2 mb-2">{{ $observation->observation }}</p>
                            @if ($observation->next_learning_goal)
                                <div class="small"><strong>पुढील ध्येय:</strong> {{ $observation->next_learning_goal }}</div>
                            @endif
                        </article>
                    @empty
                        <p class="text-secondary">अद्याप निरीक्षण नाही.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="card portal-card">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h4 mb-1">मागील ३० दिवसांचा पुरावा</h2>
            <p class="text-secondary mb-0">प्रत्येक प्रकारची अचूकता स्वतंत्र ठेवली आहे; एकच एकत्रित गुण नाही.</p>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th scope="col">दिनांक</th>
                        <th scope="col">सराव</th>
                        <th scope="col">खेळ</th>
                        <th scope="col">अनुकरण</th>
                        <th scope="col">मूल्यांकन</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($timeline as $day)
                        <tr>
                            <th scope="row">{{ $day['date'] }}</th>
                            <td>{{ $day['practice'] }}%</td>
                            <td>{{ $day['game'] }}%</td>
                            <td>{{ $day['simulation'] }}%</td>
                            <td>{{ $day['assessment'] }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">अलीकडील पुरावा नाही.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
