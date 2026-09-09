<x-layouts.app title="Assessments">
    <div class="mb-4"><h1 class="h2 portal-brand mb-1">माझ्या चाचण्या</h1><p class="text-secondary mb-0">पूर्व चाचणी, पुनर्मूल्यांकन आणि उत्तर चाचणी</p></div>
    <div class="row g-4 mb-5">
        @forelse ($tests as $test)
            @php
                $canStart = $test->in_progress_attempts_count > 0 || $test->student_attempts_count < $test->max_attempts;
            @endphp
            <div class="col-md-6 col-xl-4">
                <article class="card portal-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between gap-2 mb-3"><span class="badge text-bg-primary">{{ str_replace('_', ' ', $test->type) }}</span><span class="small text-secondary">{{ $test->duration_minutes }} मिनिटे</span></div>
                        <h2 class="h4 portal-brand">{{ $test->title_marathi }}</h2>
                        <p class="text-secondary">{{ $test->title }}</p>
                        <div class="small mb-3">{{ $test->subject->name_marathi }} · {{ $test->question_count }} प्रश्न · पातळी {{ $test->difficulty }}</div>
                        @if ($test->instructions_marathi)<p>{{ $test->instructions_marathi }}</p>@endif
                    </div>
                    <div class="card-footer bg-white border-0 p-4 pt-0">
                        @if ($canStart)
                            <form method="POST" action="{{ route('assessments.start', $test) }}">@csrf<button class="btn btn-primary w-100" type="submit">{{ $test->in_progress_attempts_count > 0 ? 'चाचणी पुढे सुरू ठेवा' : 'चाचणी सुरू करा' }}</button></form>
                        @else
                            <button class="btn btn-outline-secondary w-100" disabled>कमाल प्रयत्न पूर्ण</button>
                        @endif
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-light border">सध्या कोणतीही चाचणी उपलब्ध नाही.</div></div>
        @endforelse
    </div>
    <section>
        <h2 class="h4 portal-brand mb-3">अलीकडील निकाल</h2>
        <div class="card portal-card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>चाचणी</th><th>अचूकता</th><th>गुण</th><th>तारीख</th><th></th></tr></thead><tbody>
            @forelse ($recentAttempts as $attempt)
                <tr><td><strong>{{ $attempt->test->title_marathi }}</strong><div class="small text-secondary">{{ str_replace('_', ' ', $attempt->test->type) }}</div></td><td>{{ number_format((float) $attempt->accuracy, 0) }}%</td><td>{{ $attempt->score }} / {{ $attempt->max_score }}</td><td>{{ $attempt->submitted_at?->format('d M Y') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('assessments.attempts.result', $attempt) }}">विश्लेषण</a></td></tr>
            @empty
                <tr><td class="text-center text-secondary py-4" colspan="5">अद्याप पूर्ण चाचणी नाही.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </section>
</x-layouts.app>
