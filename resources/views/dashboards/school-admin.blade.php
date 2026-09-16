<x-layouts.app title="School dashboard">
    <header class="mb-4">
        <span class="badge text-bg-primary mb-2">SCHOOL ADMIN</span>
        <h1 class="h2 portal-brand">{{ auth()->user()->school->name_marathi ?: auth()->user()->school->name }}</h1>
        <p class="text-secondary mb-0">विद्यार्थी, मार्गदर्शक आणि शैक्षणिक रचनेचा आढावा.</p>
    </header>
    <div class="row g-3">
        <div class="col-sm-6">
            <article class="card portal-card h-100"><div class="card-body">
                <p class="text-secondary mb-1">विद्यार्थी</p><p class="display-5 fw-bold mb-0">{{ $studentCount }}</p>
            </div></article>
        </div>
        <div class="col-sm-6">
            <article class="card portal-card h-100"><div class="card-body">
                <p class="text-secondary mb-1">मार्गदर्शक</p><p class="display-5 fw-bold mb-0">{{ $mentorCount }}</p>
            </div></article>
        </div>
    </div>
</x-layouts.app>
