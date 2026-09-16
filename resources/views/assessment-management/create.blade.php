<x-layouts.app title="Create assessment">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h2 portal-brand mb-1">नवीन चाचणी</h1><p class="text-secondary mb-0">Pre-test, reassessment or post-test</p></div><a class="btn btn-outline-secondary" href="{{ route('tests.index') }}">चाचणी सूची</a></div>
    <form method="POST" action="{{ route('tests.store') }}" class="card portal-card">
        @csrf
        <div class="card-body p-4">@include('assessment-management._form')</div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">चाचणी जतन करा</button></div>
    </form>
</x-layouts.app>
