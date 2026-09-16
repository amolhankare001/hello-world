<x-layouts.app title="Schools">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 portal-brand mb-1">शाळा व्यवस्थापन</h1>
            <p class="text-secondary mb-0">प्लॅटफॉर्मवरील शाळा आणि त्यांची सक्रिय स्थिती.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('schools.create') }}">नवीन शाळा</a>
    </div>
    <div class="table-responsive card portal-card">
        <table class="table align-middle mb-0">
            <thead><tr><th>शाळा</th><th>कोड</th><th>विद्यार्थी</th><th>मार्गदर्शक</th><th>स्थिती</th><th></th></tr></thead>
            <tbody>
                @forelse ($schools as $school)
                    <tr>
                        <td>{{ $school->name_marathi ?: $school->name }}</td>
                        <td>{{ $school->code }}</td>
                        <td>{{ $school->students_count }}</td>
                        <td>{{ $school->mentors_count }}</td>
                        <td><span class="badge {{ $school->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $school->is_active ? 'सक्रिय' : 'निष्क्रिय' }}</span></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('schools.edit', $school) }}">संपादित करा</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">शाळा उपलब्ध नाहीत.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $schools->links() }}</div>
</x-layouts.app>
