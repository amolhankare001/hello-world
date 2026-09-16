<x-layouts.app title="Practice">
    <div class="mb-4"><h1 class="h2 portal-brand mb-1">माझा सराव</h1><p class="text-secondary mb-0">तुमच्या कौशल्यानुसार उपलब्ध सराव निवडा.</p></div>
    <div class="row g-4">
        @forelse ($activities as $activity)
            <div class="col-md-6 col-xl-4">
                <article class="card portal-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-3"><span class="badge text-bg-primary">{{ $activity->skill->subject->name_marathi }}</span><span class="small text-secondary">पातळी {{ $activity->difficulty }}</span></div>
                        <h2 class="h5 mt-3 mb-1">{{ $activity->title_marathi }}</h2>
                        <p class="text-secondary">{{ $activity->title }}</p>
                        <p class="mb-1">{{ $activity->instructions_marathi ?: $activity->instructions }}</p>
                        <div class="small text-secondary">{{ min($activity->practiceActivity->question_count, $activity->questions_count) }} प्रश्न · {{ $activity->estimated_minutes ?? 10 }} मिनिटे</div>
                    </div>
                    <div class="card-footer bg-white"><form method="POST" action="{{ route('practice.start', $activity) }}">@csrf<button class="btn btn-primary w-100" type="submit">सराव सुरू करा</button></form></div>
                </article>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">सध्या कोणताही प्रकाशित सराव उपलब्ध नाही.</div></div>
        @endforelse
    </div>

    @if ($recentAttempts->isNotEmpty())
        <section class="card portal-card mt-5">
            <div class="card-header bg-white"><h2 class="h5 mb-0">अलीकडील निकाल</h2></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>सराव</th><th>अचूकता</th><th>वेळ</th><th></th></tr></thead><tbody>
                @foreach ($recentAttempts as $attempt)
                    <tr><td>{{ $attempt->practiceActivity->activity->title_marathi }}</td><td>{{ number_format((float) $attempt->accuracy, 0) }}%</td><td>{{ $attempt->duration_seconds }} सेकंद</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('practice.attempts.result', $attempt) }}">निकाल पहा</a></td></tr>
                @endforeach
            </tbody></table></div>
        </section>
    @endif
</x-layouts.app>
