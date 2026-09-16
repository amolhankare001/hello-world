<x-layouts.app title="Create subject">
    <h1 class="h2 portal-brand mb-4">नवीन विषय तयार करा</h1>
    <form method="POST" action="{{ route('subjects.store') }}" class="card portal-card">
        @csrf
        <div class="card-body p-4">@include('content.subjects._form')</div>
        <div class="card-footer bg-white d-flex justify-content-between">
            <a class="btn btn-outline-secondary" href="{{ route('subjects.index') }}">रद्द करा</a>
            <button class="btn btn-primary" type="submit">विषय जतन करा</button>
        </div>
    </form>
</x-layouts.app>
