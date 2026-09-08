<x-layouts.app title="Subjects">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 portal-brand mb-1">विषय आणि कौशल्ये</h1>
            <p class="text-secondary mb-0">Subjects, skills and learning paths</p>
        </div>
        <a class="btn btn-primary" href="{{ route('subjects.create') }}">नवीन विषय</a>
    </div>
    <div class="card portal-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>विषय</th><th>Code</th><th>कौशल्ये</th><th>स्थिती</th><th></th></tr></thead>
                <tbody>
                    @forelse ($subjects as $subject)
                        <tr>
                            <td><strong>{{ $subject->name_marathi }}</strong><div class="small text-secondary">{{ $subject->name }}</div></td>
                            <td>{{ $subject->code }}</td>
                            <td>{{ $subject->skills_count }}</td>
                            <td><span class="badge text-bg-{{ $subject->is_active ? 'success' : 'secondary' }}">{{ $subject->is_active ? 'सक्रिय' : 'निष्क्रिय' }}</span></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('subjects.edit', $subject) }}">व्यवस्थापित करा</a></td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-secondary py-4" colspan="5">अद्याप विषय नाहीत.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($subjects->hasPages())<div class="card-footer bg-white">{{ $subjects->links() }}</div>@endif
    </div>
</x-layouts.app>
