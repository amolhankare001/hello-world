<x-layouts.app title="Learning activities">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h2 portal-brand mb-1">अध्ययन उपक्रम</h1><p class="text-secondary mb-0">Learning activities for lessons, practice, games, simulations and assessments</p></div>
        <a class="btn btn-primary" href="{{ route('activities.create') }}">नवीन उपक्रम</a>
    </div>
    <div class="card portal-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>उपक्रम</th><th>कौशल्य</th><th>प्रकार</th><th>व्याप्ती</th><th>स्थिती</th><th></th></tr></thead>
                <tbody>
                    @forelse ($activities as $activity)
                        <tr>
                            <td><strong>{{ $activity->title_marathi }}</strong><div class="small text-secondary">{{ $activity->title }} · {{ $activity->code }}</div></td>
                            <td>{{ $activity->skill->name_marathi }}<div class="small text-secondary">{{ $activity->skill->subject->name_marathi }}</div></td>
                            <td><span class="badge text-bg-light">{{ $activity->type }}</span></td>
                            <td>{{ $activity->school?->name_marathi ?? 'सर्व शाळा' }}</td>
                            <td><span class="badge text-bg-{{ $activity->status === 'published' ? 'success' : ($activity->status === 'draft' ? 'warning' : 'secondary') }}">{{ $activity->status }}</span></td>
                            <td class="text-end">@can('update', $activity)<a class="btn btn-sm btn-outline-primary" href="{{ route('activities.edit', $activity) }}">संपादित करा</a>@else<span class="small text-secondary">फक्त पाहण्यासाठी</span>@endcan</td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-secondary py-4" colspan="6">अद्याप उपक्रम नाहीत.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($activities->hasPages())<div class="card-footer bg-white">{{ $activities->links() }}</div>@endif
    </div>
</x-layouts.app>
