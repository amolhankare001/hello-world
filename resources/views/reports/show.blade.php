<!DOCTYPE html>
<html lang="mr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reportTitle }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background: #eef2f6; font-family: "Noto Sans Devanagari", "Nirmala UI", Mangal, sans-serif; }
        .report-sheet { width: min(210mm, calc(100% - 2rem)); min-height: 297mm; margin: 1rem auto; padding: 15mm; background: #fff; }
        .report-section { break-inside: avoid; margin-bottom: 1.5rem; }
        .report-table { font-size: .82rem; }
        @page { size: A4; margin: 12mm; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .report-sheet { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none !important; }
            a { color: inherit !important; text-decoration: none !important; }
        }
    </style>
</head>
<body>
    <div class="container-fluid no-print py-3">
        <div class="card portal-card mx-auto" style="max-width: 1200px">
            <div class="card-body">
                <form class="row g-2 align-items-end" method="GET" action="{{ route('reports.show', $reportType) }}">
                    <div class="col-md-2">
                        <label class="form-label" for="student_id">विद्यार्थी</label>
                        <select class="form-select form-select-sm" id="student_id" name="student_id">
                            <option value="">सर्व</option>
                            @foreach ($filterStudents as $student)
                                <option value="{{ $student->id }}" @selected(($filters['student_id'] ?? null) == $student->id)>{{ $student->user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="academic_year_id">शैक्षणिक वर्ष</label>
                        <select class="form-select form-select-sm" id="academic_year_id" name="academic_year_id">
                            <option value="">सर्व</option>
                            @foreach ($academicYears as $academicYear)
                                <option value="{{ $academicYear->id }}" @selected(($filters['academic_year_id'] ?? null) == $academicYear->id)>{{ $academicYear->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="subject_id">विषय</label>
                        <select class="form-select form-select-sm" id="subject_id" name="subject_id">
                            <option value="">सर्व</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected(($filters['subject_id'] ?? null) == $subject->id)>{{ $subject->name_marathi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="skill_id">कौशल्य</label>
                        <select class="form-select form-select-sm" id="skill_id" name="skill_id">
                            <option value="">सर्व</option>
                            @foreach ($skills as $skill)
                                <option value="{{ $skill->id }}" @selected(($filters['skill_id'] ?? null) == $skill->id)>{{ $skill->name_marathi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label" for="from">पासून</label>
                        <input class="form-control form-control-sm" id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label" for="to">पर्यंत</label>
                        <input class="form-control form-control-sm" id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-primary btn-sm" type="submit">लागू करा</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.print()">प्रिंट / PDF</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <main class="report-sheet shadow-sm">
        <header class="border-bottom pb-3 mb-4 d-flex justify-content-between align-items-start">
            <div>
                <h1 class="h3 mb-1">{{ $reportTitle }}</h1>
                <div class="text-secondary">वैयक्तिक उपचारात्मक अध्ययन पोर्टल</div>
            </div>
            <div class="text-end small text-secondary">
                तयार केले: {{ now()->format('d-m-Y H:i') }}<br>
                विद्यार्थी: {{ $students->count() }}
            </div>
        </header>

        @if ($reportType === 'student_progress')
            @foreach ($students as $student)
                <section class="report-section">
                    <h2 class="h5">{{ $student->user->name }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered report-table">
                            <thead><tr><th>विषय</th><th>कौशल्य</th><th>प्रयत्न</th><th>अचूकता</th><th>प्रभुत्व</th><th>स्थिती</th></tr></thead>
                            <tbody>
                                @forelse ($progress->where('student_id', $student->id) as $item)
                                    <tr>
                                        <td>{{ $item->skill->subject->name_marathi }}</td>
                                        <td>{{ $item->skill->name_marathi }}</td>
                                        <td>{{ $item->total_attempts }}</td>
                                        <td>{{ number_format((float) $item->accuracy, 1) }}%</td>
                                        <td>{{ number_format((float) $item->mastery_score, 1) }}%</td>
                                        <td>{{ $item->status }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="text-secondary text-center" colspan="6">निवडलेल्या फिल्टरसाठी नोंद नाही.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        @elseif ($reportType === 'holistic_progress')
            @foreach ($students as $student)
                @php
                    $studentEvents = $events->where('student_id', $student->id);
                    $studentProgress = $progress->where('student_id', $student->id);
                @endphp
                <section class="report-section">
                    <h2 class="h4 border-bottom pb-2">{{ $student->user->name }}</h2>
                    <h3 class="h6 mt-3">शैक्षणिक पुरावे</h3>
                    <table class="table table-sm table-bordered report-table">
                        <thead><tr><th>कौशल्य</th><th>अचूकता</th><th>पूर्व-चाचणी</th><th>उत्तर-चाचणी</th><th>सुधारणा</th></tr></thead>
                        <tbody>
                            @forelse ($studentProgress as $item)
                                <tr>
                                    <td>{{ $item->skill->name_marathi }}</td>
                                    <td>{{ number_format((float) $item->accuracy, 1) }}%</td>
                                    <td>{{ $item->pre_test_score === null ? '—' : number_format((float) $item->pre_test_score, 1).'%' }}</td>
                                    <td>{{ $item->post_test_score === null ? '—' : number_format((float) $item->post_test_score, 1).'%' }}</td>
                                    <td>{{ $item->improvement === null ? '—' : number_format((float) $item->improvement, 1).'%' }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-secondary text-center" colspan="5">शैक्षणिक नोंद नाही.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <h3 class="h6 mt-3">कृती पुरावे स्वतंत्र वर्गीकरण</h3>
                    <div class="row g-2 mb-3">
                        @foreach (['practice' => 'सराव', 'game' => 'खेळ', 'simulation' => 'अनुकरण', 'assessment' => 'मूल्यमापन'] as $activityType => $label)
                            @php
                                $activityItems = $studentEvents->where('activity_type', $activityType);
                            @endphp
                            <div class="col-6 col-md-3">
                                <div class="border rounded p-2">
                                    <strong>{{ $label }}</strong><br>
                                    <span class="small">कृती {{ $activityItems->count() }} · सरासरी {{ number_format((float) $activityItems->avg('accuracy'), 1) }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <h3 class="h6">समग्र विकास निर्देशक</h3>
                    <table class="table table-sm table-bordered report-table">
                        <thead><tr><th>क्षेत्र</th><th>निर्देशक</th><th>गुणांकन (1–5)</th><th>नोंद</th></tr></thead>
                        <tbody>
                            @forelse ($holisticRecords->where('student_id', $student->id) as $record)
                                <tr>
                                    <td>{{ $record->indicator->domain->name_marathi }}</td>
                                    <td>{{ $record->indicator->name_marathi }}</td>
                                    <td>{{ $record->rating }}</td>
                                    <td>{{ $record->notes ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-secondary text-center" colspan="4">समग्र गुणांकन नोंद नाही.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <h3 class="h6">मार्गदर्शक निरीक्षणे</h3>
                    @forelse ($observations->where('student_id', $student->id) as $observation)
                        <div class="border rounded p-3 mb-2 report-table">
                            <strong>{{ $observation->observed_on->format('d-m-Y') }} · {{ $observation->mentor->user->name }}</strong>
                            <p class="mb-1 mt-2">{{ $observation->observation }}</p>
                            <div><strong>बलस्थाने:</strong> {{ $observation->strengths ?: '—' }}</div>
                            <div><strong>सुधारणा क्षेत्र:</strong> {{ $observation->areas_for_improvement ?: '—' }}</div>
                            <div><strong>शिफारस:</strong> {{ $observation->recommended_intervention ?: '—' }}</div>
                            <div><strong>पुढील ध्येय:</strong> {{ $observation->next_learning_goal ?: '—' }}</div>
                        </div>
                    @empty
                        <p class="text-secondary">मार्गदर्शक निरीक्षण नोंद नाही.</p>
                    @endforelse
                </section>
            @endforeach
        @elseif (in_array($reportType, ['pre_test', 'post_test'], true))
            @php
                $requiredTestType = $reportType === 'pre_test' ? 'pre_test' : 'post_test';
            @endphp
            <section class="report-section">
                <table class="table table-sm table-bordered report-table">
                    <thead><tr><th>विद्यार्थी</th><th>चाचणी</th><th>विषय</th><th>गुण</th><th>टक्केवारी</th><th>दिनांक</th></tr></thead>
                    <tbody>
                        @forelse ($testAttempts->filter(fn ($attempt) => $attempt->test->type === $requiredTestType) as $attempt)
                            <tr>
                                <td>{{ $attempt->student->user->name }}</td>
                                <td>{{ $attempt->test->title_marathi }}</td>
                                <td>{{ $attempt->test->subject->name_marathi }}</td>
                                <td>{{ number_format((float) $attempt->score, 1) }} / {{ number_format((float) $attempt->max_score, 1) }}</td>
                                <td>{{ number_format((float) $attempt->percentage, 1) }}%</td>
                                <td>{{ $attempt->submitted_at?->format('d-m-Y') }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-secondary text-center" colspan="6">चाचणी नोंद नाही.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        @elseif ($reportType === 'pre_post_improvement')
            <section class="report-section">
                <table class="table table-sm table-bordered report-table">
                    <thead><tr><th>विद्यार्थी</th><th>कौशल्य</th><th>पूर्व-चाचणी</th><th>उत्तर-चाचणी</th><th>सुधारणा</th></tr></thead>
                    <tbody>
                        @forelse ($progress->filter(fn ($item) => $item->pre_test_score !== null || $item->post_test_score !== null) as $item)
                            <tr>
                                <td>{{ $item->student->user->name }}</td>
                                <td>{{ $item->skill->name_marathi }}</td>
                                <td>{{ $item->pre_test_score === null ? '—' : number_format((float) $item->pre_test_score, 1).'%' }}</td>
                                <td>{{ $item->post_test_score === null ? '—' : number_format((float) $item->post_test_score, 1).'%' }}</td>
                                <td>{{ $item->improvement === null ? '—' : number_format((float) $item->improvement, 1).'%' }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-secondary text-center" colspan="5">पूर्व/उत्तर तुलना नोंद नाही.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        @elseif ($reportType === 'skill_wise')
            <section class="report-section">
                <table class="table table-sm table-bordered report-table">
                    <thead><tr><th>विद्यार्थी</th><th>विषय</th><th>कौशल्य</th><th>प्रयत्न</th><th>अचूकता</th><th>प्रभुत्व</th><th>स्थिती</th></tr></thead>
                    <tbody>
                        @forelse ($progress as $item)
                            <tr>
                                <td>{{ $item->student->user->name }}</td>
                                <td>{{ $item->skill->subject->name_marathi }}</td>
                                <td>{{ $item->skill->name_marathi }}</td>
                                <td>{{ $item->total_attempts }}</td>
                                <td>{{ number_format((float) $item->accuracy, 1) }}%</td>
                                <td>{{ number_format((float) $item->mastery_score, 1) }}%</td>
                                <td>{{ $item->status }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-secondary text-center" colspan="7">कौशल्य नोंद नाही.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        @elseif (in_array($reportType, ['game_performance', 'practice'], true))
            @php
                $requiredActivityType = $reportType === 'game_performance' ? 'game' : 'practice';
            @endphp
            <section class="report-section">
                <table class="table table-sm table-bordered report-table">
                    <thead><tr><th>विद्यार्थी</th><th>कौशल्य</th><th>गुण</th><th>अचूकता</th><th>कालावधी</th><th>दिनांक</th></tr></thead>
                    <tbody>
                        @forelse ($events->where('activity_type', $requiredActivityType) as $event)
                            <tr>
                                <td>{{ $event->student->user->name }}</td>
                                <td>{{ $event->skill->name_marathi }}</td>
                                <td>{{ $event->score === null ? '—' : $event->score.' / '.$event->max_score }}</td>
                                <td>{{ number_format((float) $event->accuracy, 1) }}%</td>
                                <td>{{ $event->duration_seconds === null ? '—' : round($event->duration_seconds / 60).' मिनिटे' }}</td>
                                <td>{{ $event->occurred_at->format('d-m-Y') }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-secondary text-center" colspan="6">कृती नोंद नाही.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        @elseif ($reportType === 'class_group')
            <section class="report-section">
                <table class="table table-sm table-bordered report-table">
                    <thead><tr><th>विद्यार्थी</th><th>कौशल्ये</th><th>सरासरी अचूकता</th><th>प्रभुत्व</th><th>सराव</th><th>खेळ</th><th>अनुकरण</th><th>मूल्यमापन</th></tr></thead>
                    <tbody>
                        @forelse ($classSummaries as $summary)
                            <tr>
                                <td>{{ $summary['student']->user->name }}</td>
                                <td>{{ $summary['skills'] }}</td>
                                <td>{{ number_format($summary['average_accuracy'], 1) }}%</td>
                                <td>{{ $summary['mastered'] }}</td>
                                <td>{{ $summary['practice'] }}</td>
                                <td>{{ $summary['games'] }}</td>
                                <td>{{ $summary['simulations'] }}</td>
                                <td>{{ $summary['assessments'] }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-secondary text-center" colspan="8">विद्यार्थी नोंद नाही.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        @elseif ($reportType === 'mentor_intervention')
            <section class="report-section">
                <table class="table table-sm table-bordered report-table">
                    <thead><tr><th>विद्यार्थी</th><th>मार्गदर्शक</th><th>कौशल्य</th><th>हस्तक्षेप</th><th>स्थिती</th><th>कालावधी</th><th>परिणाम</th></tr></thead>
                    <tbody>
                        @forelse ($interventions as $intervention)
                            <tr>
                                <td>{{ $intervention->student->user->name }}</td>
                                <td>{{ $intervention->mentor->user->name }}</td>
                                <td>{{ $intervention->skill->name_marathi }}</td>
                                <td>{{ $intervention->title }}</td>
                                <td>{{ $intervention->status }}</td>
                                <td>{{ $intervention->starts_on?->format('d-m-Y') }} – {{ $intervention->completed_on?->format('d-m-Y') ?: 'सुरू' }}</td>
                                <td>{{ $intervention->outcome ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-secondary text-center" colspan="7">हस्तक्षेप नोंद नाही.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        @endif
    </main>
</body>
</html>
