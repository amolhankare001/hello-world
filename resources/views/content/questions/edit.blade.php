<x-layouts.app title="Edit practice question">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h2 portal-brand mb-1">प्रश्न संपादन</h1><p class="text-secondary mb-0">{{ $activity->title_marathi }} / {{ $activity->title }}</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('activities.edit', $activity) }}">सरावाकडे परत</a>
    </div>
    <form method="POST" action="{{ route('activities.questions.update', [$activity, $question]) }}" class="card portal-card">
        @csrf
        @method('PUT')
        <div class="card-body p-4">@include('content.questions._form')</div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">प्रश्न जतन करा</button></div>
    </form>
</x-layouts.app>
