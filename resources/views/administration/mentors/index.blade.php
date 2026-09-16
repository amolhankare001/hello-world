<x-layouts.app title="Mentors">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h2 portal-brand mb-1">मार्गदर्शक व्यवस्थापन</h1><p class="text-secondary mb-0">मार्गदर्शक खाती आणि विद्यार्थ्यांची संख्या.</p></div>
        <a class="btn btn-primary" href="{{ route('mentors.create') }}">नवीन मार्गदर्शक</a>
    </div>
    <div class="table-responsive card portal-card">
        <table class="table align-middle mb-0">
            <thead><tr><th>मार्गदर्शक</th><th>कर्मचारी क्रमांक</th><th>फोन</th><th>नियुक्त विद्यार्थी</th><th>स्थिती</th><th></th></tr></thead>
            <tbody>
                @forelse ($mentors as $mentor)
                    <tr>
                        <td><strong>{{ $mentor->user->name }}</strong><br><small class="text-secondary">{{ $mentor->user->email }}</small></td>
                        <td>{{ $mentor->employee_number ?: '—' }}</td>
                        <td>{{ $mentor->phone ?: '—' }}</td>
                        <td>{{ $mentor->student_assignments_count }}</td>
                        <td><span class="badge {{ $mentor->user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $mentor->user->is_active ? 'सक्रिय' : 'निष्क्रिय' }}</span></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('mentors.edit', $mentor) }}">संपादित करा</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">मार्गदर्शक उपलब्ध नाहीत.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $mentors->links() }}</div>
</x-layouts.app>
