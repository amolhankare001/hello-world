<x-layouts.app title="Create school">
    <div class="mb-4"><h1 class="h2 portal-brand">नवीन शाळा</h1></div>
    <form method="POST" action="{{ route('schools.store') }}" class="card portal-card">
        @csrf
        <div class="card-body p-4">@include('administration.schools._form')</div>
        <div class="card-footer bg-white d-flex gap-2 justify-content-end">
            <a class="btn btn-outline-secondary" href="{{ route('schools.index') }}">रद्द करा</a>
            <button class="btn btn-primary" type="submit">शाळा जतन करा</button>
        </div>
    </form>
</x-layouts.app>
