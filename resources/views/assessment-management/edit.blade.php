<x-layouts.app title="Edit assessment">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h2 portal-brand mb-1">चाचणी संपादित करा</h1><p class="text-secondary mb-0">{{ $test->title_marathi }} / {{ $test->title }}</p></div><a class="btn btn-outline-secondary" href="{{ route('tests.show', $test) }}">निकाल पहा</a></div>
    <form method="POST" action="{{ route('tests.update', $test) }}" class="card portal-card">
        @csrf
        @method('PUT')
        <div class="card-body p-4">@include('assessment-management._form')</div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">बदल जतन करा</button></div>
    </form>
</x-layouts.app>
