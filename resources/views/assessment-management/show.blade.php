<x-layouts.app title="Assessment results">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><span class="badge text-bg-primary mb-2">{{ str_replace('_', ' ', $test->type) }}</span><h1 class="h2 portal-brand mb-1">{{ $test->title_marathi }}</h1><p class="text-secondary mb-0">{{ $test->title }} · {{ $test->subject->name_marathi }}</p></div><div class="d-flex gap-2">@can('update', $test)<a class="btn btn-outline-primary" href="{{ route('tests.edit', $test) }}">संपादित करा</a>@endcan<a class="btn btn-outline-secondary" href="{{ route('tests.index') }}">चाचणी सूची</a></div></div>
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <section class="card portal-card h-100"><div class="card-body"><h2 class="h5">चाचणी रचना</h2><div class="row g-3"><div class="col-sm-4"><span class="small text-secondary d-block">वर्ग</span>{{ $test->schoolClass?->name_marathi ?? 'सर्व वर्ग' }}</div><div class="col-sm-4"><span class="small text-secondary d-block">कालावधी</span>{{ $test->duration_minutes }} मिनिटे</div><div class="col-sm-4"><span class="small text-secondary d-block">प्रश्न</span>{{ $test->question_count }} / {{ $test->questions->count() }}</div></div><hr><div class="d-flex flex-wrap gap-2">@foreach ($test->questions->groupBy('skill_id') as $skillQuestions)<span class="badge text-bg-light">{{ $skillQuestions->first()->skill->name_marathi }} · {{ $skillQuestions->count() }}</span>@endforeach</div></div></section>
        </div>
        <div class="col-lg-4">
            <section class="card portal-card h-100"><div class="card-body"><h2 class="h5">सामान्य चुका</h2>@forelse ($commonErrors as $error)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $error['name_marathi'] ?: $error['name'] }}</span><strong>{{ $error['count'] }}</strong></div>@empty<p class="text-secondary mb-0">अद्याप त्रुटी पुरावा नाही.</p>@endforelse</div></section>
        </div>
    </div>
    <section class="card portal-card">
        <div class="card-header bg-white"><h2 class="h5 mb-0">विद्यार्थी निकाल</h2></div>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>विद्यार्थी</th><th>गुण</th><th>अचूकता</th><th>वेळ</th><th>तारीख</th><th></th></tr></thead><tbody>
            @forelse ($test->attempts as $attempt)
                <tr><td><strong>{{ $attempt->student->user->name }}</strong><div class="small text-secondary">{{ $attempt->student->student_number }}</div></td><td>{{ $attempt->score }} / {{ $attempt->max_score }}</td><td>{{ number_format((float) $attempt->accuracy, 0) }}%</td><td>{{ $attempt->duration_seconds }} सेकंद</td><td>{{ $attempt->submitted_at?->format('d M Y') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('tests.attempts.show', [$test, $attempt]) }}">विश्लेषण</a></td></tr>
            @empty
                <tr><td class="text-center text-secondary py-4" colspan="6">अद्याप कोणताही पूर्ण प्रयत्न नाही.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</x-layouts.app>
