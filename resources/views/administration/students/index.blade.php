<x-layouts.app title="Students">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h2 portal-brand mb-1">विद्यार्थी व्यवस्थापन</h1><p class="text-secondary mb-0">प्रवेश, वर्ग आणि मार्गदर्शक नियुक्ती.</p></div>
        <a class="btn btn-primary" href="{{ route('students.create') }}">नवीन विद्यार्थी</a>
    </div>
    <div class="table-responsive card portal-card">
        <table class="table align-middle mb-0">
            <thead><tr><th>विद्यार्थी</th><th>क्रमांक</th><th>वर्ग</th><th>मार्गदर्शक</th><th>स्थिती</th><th></th></tr></thead>
            <tbody>
                @forelse ($students as $student)
                    @php
                        $enrollment = $student->enrollments->first();
                        $assignment = $student->mentorAssignments->first();
                    @endphp
                    <tr>
                        <td><strong>{{ $student->user->name }}</strong><br><small class="text-secondary">{{ $student->user->email }}</small></td>
                        <td>{{ $student->student_number }}</td>
                        <td>{{ $enrollment?->division?->schoolClass?->name_marathi ?: $enrollment?->division?->schoolClass?->name }} {{ $enrollment?->division?->name_marathi ?: $enrollment?->division?->name }}</td>
                        <td>{{ $assignment?->mentor?->user?->name ?: 'नियुक्त नाही' }}</td>
                        <td><span class="badge {{ $student->user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $student->user->is_active ? 'सक्रिय' : 'निष्क्रिय' }}</span></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('students.edit', $student) }}">संपादित करा</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">विद्यार्थी उपलब्ध नाहीत.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $students->links() }}</div>
</x-layouts.app>
