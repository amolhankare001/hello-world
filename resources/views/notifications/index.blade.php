<x-layouts.app title="सूचना">
    <header class="mb-4 d-flex flex-column flex-md-row justify-content-between gap-3">
        <div>
            <h1 class="h2 portal-brand mb-1">सूचना</h1>
            <p class="text-secondary mb-0">अध्ययन ध्येय, प्रगती आणि आवश्यक कृती.</p>
        </div>
        @if ($unreadNotificationCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn btn-outline-primary" type="submit">सर्व वाचल्या म्हणून नोंदवा</button>
            </form>
        @endif
    </header>

    <section class="card portal-card">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <button class="list-group-item list-group-item-action p-4 text-start {{ $notification->read_at ? '' : 'bg-primary-subtle' }}" type="submit">
                        <div class="d-flex justify-content-between gap-3">
                            <strong>{{ $notification->data['title'] }}</strong>
                            <span class="small text-secondary">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-secondary mt-1">{{ $notification->data['message'] }}</div>
                    </button>
                </form>
            @empty
                <div class="p-4 text-secondary">अद्याप सूचना नाहीत.</div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-body">{{ $notifications->links() }}</div>
        @endif
    </section>
</x-layouts.app>
