<x-layouts.app title="Assessments">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="h2 portal-brand mb-1">चाचणी व्यवस्थापन</h1><p class="text-secondary mb-0">Pre-tests, reassessments and post-tests</p></div><a class="btn btn-primary" href="{{ route('tests.create') }}">नवीन चाचणी</a></div>
    <div class="card portal-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>चाचणी</th><th>विषय व वर्ग</th><th>प्रकार</th><th>प्रश्न</th><th>पूर्ण प्रयत्न</th><th>स्थिती</th><th></th></tr></thead>
                <tbody>
                    @forelse ($tests as $test)
                        <tr>
                            <td><strong>{{ $test->title_marathi }}</strong><div class="small text-secondary">{{ $test->title }} · {{ $test->code }}</div></td>
                            <td>{{ $test->subject->name_marathi }}<div class="small text-secondary">{{ $test->schoolClass?->name_marathi ?? 'सर्व वर्ग' }} · {{ $test->school?->name_marathi ?? 'सर्व शाळा' }}</div></td>
                            <td><span class="badge text-bg-light">{{ str_replace('_', ' ', $test->type) }}</span></td>
                            <td>{{ $test->question_count }} / {{ $test->questions_count }}</td>
                            <td>{{ $test->completed_attempts_count }}</td>
                            <td><span class="badge text-bg-{{ $test->status === 'published' ? 'success' : ($test->status === 'draft' ? 'warning' : 'secondary') }}">{{ $test->status }}</span></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('tests.show', $test) }}">पहा</a></td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-secondary py-4" colspan="7">अद्याप चाचण्या नाहीत.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tests->hasPages())<div class="card-footer bg-white">{{ $tests->links() }}</div>@endif
    </div>
</x-layouts.app>
