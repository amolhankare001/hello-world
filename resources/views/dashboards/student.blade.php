<x-layouts.app title="Student dashboard">
    <header class="mb-4">
        <span class="badge text-bg-warning mb-2">STUDENT</span>
        <h1 class="h2 portal-brand">नमस्कार, {{ auth()->user()->name }}</h1>
        <p class="text-secondary mb-0">आजचे अध्ययन तुमच्या स्वतःच्या प्रगतीवर केंद्रित आहे.</p>
    </header>
    <div class="row g-3">
        <div class="col-md-6">
            <article class="card portal-card h-100"><div class="card-body">
                <h2 class="h5">माझा वर्ग</h2>
                @if ($enrollment)
                    <p class="display-6 fw-bold mb-1">{{ $enrollment->division->schoolClass->name_marathi ?: $enrollment->division->schoolClass->name }} – {{ $enrollment->division->name_marathi ?: $enrollment->division->name }}</p>
                    <p class="text-secondary mb-0">{{ $enrollment->academicYear->name }}</p>
                @else
                    <p class="text-secondary mb-0">सक्रिय प्रवेश नोंद उपलब्ध नाही.</p>
                @endif
            </div></article>
        </div>
        <div class="col-md-6">
            <article class="card portal-card h-100"><div class="card-body d-flex flex-column">
                <h2 class="h5">कौशल्य प्रगती</h2>
                <p class="display-6 fw-bold mb-1">{{ $trackedSkillCount }}</p>
                <p class="text-secondary">कौशल्यांची वैयक्तिक नोंद सुरू आहे.</p>
                <a class="btn btn-primary mt-auto" href="{{ route('practice.index') }}">सराव निवडा</a>
            </div></article>
        </div>
    </div>
</x-layouts.app>
