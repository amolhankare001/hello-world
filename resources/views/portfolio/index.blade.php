<x-layouts.app :title="$student->user->name.' portfolio'">
    <header class="mb-4">
        @if (auth()->user()->hasRole(\App\Enums\RoleCode::Mentor))
            <a class="small text-decoration-none" href="{{ route('mentor.students.show', $student) }}">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>विद्यार्थी प्रगती
            </a>
        @endif
        <h1 class="h2 portal-brand mt-2 mb-1">विद्यार्थी पोर्टफोलिओ</h1>
        <p class="text-secondary mb-0">{{ $student->user->name }} · खाजगी आणि परवानगी-नियंत्रित पुरावे</p>
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

    @if ($canUpload)
        <section class="card portal-card mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h4 mb-1">नवा पुरावा अपलोड करा</h2>
                <p class="text-secondary mb-0">PDF, JPG, PNG किंवा DOCX · कमाल 10 MB</p>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('mentor.students.portfolio.store', $student) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="type">श्रेणी</label>
                            <select class="form-select" id="type" name="type" required>
                                @foreach ([
                                    'pre_test' => 'पूर्व-चाचणी',
                                    'post_test' => 'उत्तर-चाचणी',
                                    'game' => 'खेळ',
                                    'practice' => 'सराव',
                                    'simulation' => 'अनुकरण',
                                    'teacher_observation' => 'शिक्षक निरीक्षण',
                                    'student_work' => 'विद्यार्थी काम',
                                    'certificate' => 'प्रमाणपत्र',
                                    'progress_report' => 'प्रगती अहवाल',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="title">शीर्षक</label>
                            <input class="form-control" id="title" name="title" value="{{ old('title') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="occurred_on">पुरावा दिनांक</label>
                            <input class="form-control" id="occurred_on" name="occurred_on" type="date" value="{{ old('occurred_on', now()->toDateString()) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="skill_id">कौशल्य (ऐच्छिक)</label>
                            <select class="form-select" id="skill_id" name="skill_id">
                                <option value="">कौशल्य निवडलेले नाही</option>
                                @foreach ($skills as $skill)
                                    <option value="{{ $skill->id }}" @selected((string) old('skill_id') === (string) $skill->id)>
                                        {{ $skill->subject->name_marathi }} · {{ $skill->name_marathi }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="file">फाइल</label>
                            <input class="form-control" id="file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">वर्णन</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">सुरक्षित अपलोड</button>
                </form>
            </div>
        </section>
    @endif

    <section class="card portal-card">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h4 mb-3">जतन केलेले पुरावे</h2>
            <form class="row g-2" method="GET">
                <div class="col-md-5">
                    <label class="visually-hidden" for="academic_year_id">शैक्षणिक वर्ष</label>
                    <select class="form-select" id="academic_year_id" name="academic_year_id">
                        <option value="">सर्व शैक्षणिक वर्षे</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" @selected((string) ($filters['academic_year_id'] ?? '') === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="visually-hidden" for="filter_type">श्रेणी</label>
                    <select class="form-select" id="filter_type" name="type">
                        <option value="">सर्व श्रेणी</option>
                        @foreach ([
                            'pre_test' => 'पूर्व-चाचणी',
                            'post_test' => 'उत्तर-चाचणी',
                            'game' => 'खेळ',
                            'practice' => 'सराव',
                            'simulation' => 'अनुकरण',
                            'teacher_observation' => 'शिक्षक निरीक्षण',
                            'student_work' => 'विद्यार्थी काम',
                            'certificate' => 'प्रमाणपत्र',
                            'progress_report' => 'प्रगती अहवाल',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100" type="submit">फिल्टर</button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @forelse ($items as $item)
                    <div class="col-12 col-lg-6">
                        <article class="border rounded-4 p-3 h-100">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <span class="badge text-bg-light">{{ str_replace('_', ' ', $item->type) }}</span>
                                <span class="small text-secondary">{{ $item->occurred_on?->format('d-m-Y') ?? $item->created_at->format('d-m-Y') }}</span>
                            </div>
                            <h3 class="h5">{{ $item->title }}</h3>
                            @if ($item->description)
                                <p class="text-secondary">{{ $item->description }}</p>
                            @endif
                            @if ($item->skill)
                                <div class="small mb-3">{{ $item->skill->subject->name_marathi }} · {{ $item->skill->name_marathi }}</div>
                            @endif
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('portfolio-items.show', $item) }}" target="_blank" rel="noopener">पहा</a>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('portfolio-items.download', $item) }}">डाउनलोड</a>
                                @can('delete', $item)
                                    <form method="POST" action="{{ route('mentor.portfolio-items.destroy', $item) }}" onsubmit="return confirm('हा पुरावा हटवायचा?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">हटवा</button>
                                    </form>
                                @endcan
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12"><p class="text-secondary mb-0">या फिल्टरसाठी पुरावा उपलब्ध नाही.</p></div>
                @endforelse
            </div>
            <div class="mt-4">{{ $items->links() }}</div>
        </div>
    </section>
</x-layouts.app>
