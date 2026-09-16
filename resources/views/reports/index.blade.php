<x-layouts.app title="अहवाल">
    <header class="mb-4">
        <h1 class="h2 portal-brand mb-1">अहवाल केंद्र</h1>
        <p class="text-secondary mb-0">शैक्षणिक, कृती, निरीक्षण आणि हस्तक्षेप पुरावे स्वतंत्रपणे पाहा.</p>
    </header>

    <section class="card portal-card mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h4 mb-1">अहवाल फिल्टर</h2>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="report_student_id">विद्यार्थी</label>
                    <select class="form-select" id="report_student_id">
                        <option value="">सर्व उपलब्ध विद्यार्थी</option>
                        @foreach ($filterStudents as $student)
                            <option value="{{ $student->id }}">{{ $student->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="report_academic_year_id">शैक्षणिक वर्ष</label>
                    <select class="form-select" id="report_academic_year_id">
                        <option value="">सर्व वर्षे</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}">{{ $academicYear->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="report_subject_id">विषय</label>
                    <select class="form-select" id="report_subject_id">
                        <option value="">सर्व विषय</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name_marathi }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="report_skill_id">कौशल्य</label>
                    <select class="form-select" id="report_skill_id">
                        <option value="">सर्व कौशल्ये</option>
                        @foreach ($skills as $skill)
                            <option value="{{ $skill->id }}">{{ $skill->subject->name_marathi }} · {{ $skill->name_marathi }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="report_from">पासून</label>
                    <input class="form-control" id="report_from" type="date">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="report_to">पर्यंत</label>
                    <input class="form-control" id="report_to" type="date">
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3">
        @foreach ($reports as $type => $title)
            <div class="col-12 col-md-6 col-xl-4">
                <a class="card portal-card h-100 text-decoration-none report-link" data-base-url="{{ route('reports.show', $type) }}" href="{{ route('reports.show', $type) }}">
                    <div class="card-body">
                        <i class="bi bi-file-earmark-text fs-2 text-primary" aria-hidden="true"></i>
                        <h2 class="h5 text-dark mt-3">{{ $title }}</h2>
                        <p class="text-secondary mb-0">फिल्टरसह A4 प्रिंट किंवा PDF म्हणून जतन करा.</p>
                    </div>
                </a>
            </div>
        @endforeach
    </section>

    @pushOnce('scripts')
        <script>
            const reportFilters = ['student_id', 'academic_year_id', 'subject_id', 'skill_id', 'from', 'to'];
            document.querySelectorAll('.report-link').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const url = new URL(link.dataset.baseUrl, window.location.origin);
                    reportFilters.forEach((filter) => {
                        const value = document.getElementById(`report_${filter}`).value;
                        if (value) {
                            url.searchParams.set(filter, value);
                        }
                    });
                    window.location.assign(url);
                });
            });
        </script>
    @endPushOnce
</x-layouts.app>
