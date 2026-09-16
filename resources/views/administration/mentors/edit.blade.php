<x-layouts.app title="Edit mentor">
    <h1 class="h2 portal-brand mb-4">मार्गदर्शक संपादित करा</h1>
    <form method="POST" action="{{ route('mentors.update', $mentor) }}" class="card portal-card">
        @csrf
        @method('PUT')
        <div class="card-body p-4">@include('administration.mentors._form')</div>
        <div class="card-footer bg-white d-flex gap-2 justify-content-end"><a class="btn btn-outline-secondary" href="{{ route('mentors.index') }}">रद्द करा</a><button class="btn btn-primary" type="submit">बदल जतन करा</button></div>
    </form>
</x-layouts.app>
