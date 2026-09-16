<x-layouts.app title="Mentor dashboard">
    <header class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <span class="badge text-bg-success mb-2">MENTOR</span>
            <h1 class="h2 portal-brand mb-1">कृती-केंद्रित मार्गदर्शक डॅशबोर्ड</h1>
            <p class="text-secondary mb-0">जोखीम, शिफारसी आणि सुरू असलेल्या मदतीवर त्वरित कृती करा.</p>
        </div>
        <form method="POST" action="{{ route('mentor.recommendations.refresh') }}">
            @csrf
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>पुराव्यावरून शिफारसी अद्ययावत करा
            </button>
        </form>
    </header>

    <section class="row g-3 mb-4" aria-label="Action summary">
        <div class="col-12 col-md-4">
            <div class="card portal-card h-100 border-start border-danger border-4">
                <div class="card-body">
                    <span class="text-secondary">तातडीची मदत</span>
                    <div class="display-6 fw-bold text-danger">{{ $redCount }}</div>
                    <span class="small">RED शिफारसी</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card portal-card h-100 border-start border-warning border-4">
                <div class="card-body">
                    <span class="text-secondary">अधिक सराव</span>
                    <div class="display-6 fw-bold text-warning-emphasis">{{ $yellowCount }}</div>
                    <span class="small">YELLOW शिफारसी</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card portal-card h-100 border-start border-primary border-4">
                <div class="card-body">
                    <span class="text-secondary">सुरू योजना</span>
                    <div class="display-6 fw-bold portal-brand">{{ $openInterventionCount }}</div>
                    <span class="small">नियोजित किंवा सक्रिय हस्तक्षेप</span>
                </div>
            </div>
        </div>
    </section>

    <section class="card portal-card mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h4 mb-1">प्राधान्य शिफारसी</h2>
            <p class="text-secondary mb-0">RED प्रथम; प्रत्येक शिफारस स्वीकारा, बदला किंवा नाकारा.</p>
        </div>
        <div class="card-body p-0">
            @if ($recommendations->isEmpty())
                <p class="p-4 mb-0 text-secondary">सध्या प्रलंबित शिफारस नाही. नवीन पुरावा असल्यास अद्ययावत करा.</p>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($recommendations as $recommendation)
                        <article class="list-group-item p-4">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge {{ $recommendation->risk_level === 'red' ? 'text-bg-danger' : 'text-bg-warning' }}">
                                            {{ strtoupper($recommendation->risk_level) }}
                                        </span>
                                        @if ($recommendation->status === 'modified')
                                            <span class="badge text-bg-info">बदललेली</span>
                                        @endif
                                    </div>
                                    <h3 class="h5 mb-1">{{ $recommendation->student->user->name }} · {{ $recommendation->skill->name_marathi }}</h3>
                                    <p class="text-secondary mb-2">{{ $recommendation->reason_marathi }}</p>
                                    <span class="small">
                                        अचूकता {{ $recommendation->metrics['accuracy'] }}% ·
                                        प्रभुत्व {{ $recommendation->metrics['mastery'] }}% ·
                                        अलीकडील कल {{ $recommendation->metrics['recent_trend'] }}
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap align-content-start gap-2">
                                    <a class="btn btn-outline-primary" href="{{ route('mentor.students.show', $recommendation->student) }}">प्रगती पहा</a>
                                    <a class="btn btn-outline-secondary" href="{{ route('mentor.recommendations.edit', $recommendation) }}">बदला</a>
                                    <form method="POST" action="{{ route('mentor.recommendations.accept', $recommendation) }}">
                                        @csrf
                                        <button class="btn btn-success" type="submit">स्वीकारा</button>
                                    </form>
                                    <form method="POST" action="{{ route('mentor.recommendations.reject', $recommendation) }}">
                                        @csrf
                                        <button class="btn btn-outline-danger" type="submit">नाकारा</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="card portal-card">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h4 mb-0">माझे विद्यार्थी</h2>
        </div>
        <div class="card-body">
            @if ($studentCards->isEmpty())
                <p class="mb-0 text-secondary">सध्या कोणताही विद्यार्थी नियुक्त केलेला नाही.</p>
            @else
                <div class="row g-3">
                    @foreach ($studentCards as $card)
                        @php
                            $assignment = $card['assignment'];
                        @endphp
                        <div class="col-12 col-md-6 col-xl-4">
                            <a class="card h-100 text-decoration-none text-body border mentor-student-card"
                                href="{{ route('mentor.students.show', $assignment->student) }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between gap-2 mb-3">
                                        <div>
                                            <h3 class="h5 mb-1">{{ $assignment->student->user->name }}</h3>
                                            <span class="text-secondary">{{ $assignment->student->student_number }}</span>
                                        </div>
                                        <span class="risk-dot risk-dot-{{ $card['risk_level'] }}" aria-label="{{ strtoupper($card['risk_level']) }}"></span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge text-bg-danger">{{ $card['red_count'] }} RED</span>
                                        <span class="badge text-bg-warning">{{ $card['yellow_count'] }} YELLOW</span>
                                        <span class="badge text-bg-primary">{{ $card['open_interventions'] }} योजना</span>
                                        @if ($assignment->is_primary)
                                            <span class="badge text-bg-light">Primary</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.app>
