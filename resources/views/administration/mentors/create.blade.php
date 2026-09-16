<x-layouts.app title="Create mentor">
    <h1 class="h2 portal-brand mb-4">नवीन मार्गदर्शक</h1>
    <form method="POST" action="{{ route('mentors.store') }}" class="card portal-card">
        @csrf
        <div class="card-body p-4">@include('administration.mentors._form')</div>
        <div class="card-footer bg-white d-flex gap-2 justify-content-end"><a class="btn btn-outline-secondary" href="{{ route('mentors.index') }}">रद्द करा</a><button class="btn btn-primary" type="submit">मार्गदर्शक जतन करा</button></div>
    </form>
</x-layouts.app>
