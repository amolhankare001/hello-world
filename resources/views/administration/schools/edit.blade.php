<x-layouts.app title="Edit school">
    <div class="mb-4"><h1 class="h2 portal-brand">शाळा संपादित करा</h1></div>
    <form method="POST" action="{{ route('schools.update', $school) }}" class="card portal-card">
        @csrf
        @method('PUT')
        <div class="card-body p-4">@include('administration.schools._form')</div>
        <div class="card-footer bg-white d-flex gap-2 justify-content-end">
            <a class="btn btn-outline-secondary" href="{{ route('schools.index') }}">रद्द करा</a>
            <button class="btn btn-primary" type="submit">बदल जतन करा</button>
        </div>
    </form>
</x-layouts.app>
