<x-layouts.app title="Edit learning activity">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h2 portal-brand mb-1">{{ $activity->title_marathi }}</h1><p class="text-secondary mb-0">{{ $activity->title }}</p></div><a class="btn btn-outline-secondary" href="{{ route('activities.index') }}">उपक्रम सूची</a></div>
    <form method="POST" action="{{ route('activities.update', $activity) }}" class="card portal-card">
        @csrf
        @method('PUT')
        <div class="card-body p-4">@include('content.activities._form')</div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">बदल जतन करा</button></div>
    </form>
</x-layouts.app>
