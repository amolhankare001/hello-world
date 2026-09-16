<x-layouts.app title="Create learning activity">
    <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h2 portal-brand mb-0">नवीन अध्ययन उपक्रम</h1><a class="btn btn-outline-secondary" href="{{ route('activities.index') }}">उपक्रम सूची</a></div>
    <form method="POST" action="{{ route('activities.store') }}" class="card portal-card">
        @csrf
        <div class="card-body p-4">@include('content.activities._form')</div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">उपक्रम जतन करा</button></div>
    </form>
</x-layouts.app>
