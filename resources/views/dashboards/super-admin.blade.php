<x-layouts.app title="Platform dashboard">
    <header class="mb-4">
        <span class="badge text-bg-primary mb-2">SUPER ADMIN</span>
        <h1 class="h2 portal-brand">प्लॅटफॉर्म आढावा</h1>
        <p class="text-secondary mb-0">सर्व शाळांचे सुरक्षित व्यवस्थापन.</p>
    </header>
    <div class="row g-3">
        <div class="col-sm-6">
            <article class="card portal-card h-100"><div class="card-body">
                <p class="text-secondary mb-1">Schools</p><p class="display-5 fw-bold mb-0">{{ $schoolCount }}</p>
            </div></article>
        </div>
        <div class="col-sm-6">
            <article class="card portal-card h-100"><div class="card-body">
                <p class="text-secondary mb-1">Users</p><p class="display-5 fw-bold mb-0">{{ $userCount }}</p>
            </div></article>
        </div>
    </div>
</x-layouts.app>
