<x-layouts.app title="Assessment result">
    @php
        $comparison = data_get($attempt->diagnosis, 'comparison', []);
    @endphp
    <div class="card portal-card mb-4"><div class="card-body p-4"><div class="row align-items-center g-4"><div class="col-md"><span class="badge text-bg-success mb-2">चाचणी पूर्ण</span><h1 class="h2 portal-brand">{{ $attempt->test->title_marathi }}</h1><p class="text-secondary mb-0">{{ $attempt->test->title }}@if ($isMentorView) · {{ $attempt->student->user->name }}@endif</p></div><div class="col-md-auto text-md-end"><div class="display-4 fw-bold text-primary">{{ number_format((float) $attempt->percentage, 0) }}%</div><div>{{ $attempt->score }} / {{ $attempt->max_score }} गुण · {{ number_format((float) $attempt->accuracy, 0) }}% अचूकता</div><div>{{ data_get($attempt->diagnosis, 'correct_count') }} / {{ count(data_get($attempt->diagnosis, 'question_ids', [])) }} बरोबर · {{ $attempt->duration_seconds }} सेकंद</div></div></div></div></div>

    @if (data_get($comparison, 'pre_test_percentage') !== null)
        <section class="card portal-card mb-4"><div class="card-body p-4"><h2 class="h4 portal-brand mb-3">सुधारणा विश्लेषण</h2><div class="row g-3 text-center"><div class="col-sm-4"><div class="small text-secondary">पूर्व चाचणी</div><div class="h2 mb-0">{{ number_format((float) $comparison['pre_test_percentage'], 0) }}%</div></div><div class="col-sm-4"><div class="small text-secondary">उत्तर चाचणी</div><div class="h2 mb-0">{{ number_format((float) $comparison['post_test_percentage'], 0) }}%</div></div><div class="col-sm-4"><div class="small text-secondary">टक्केवारी गुण सुधारणा</div><div class="h2 mb-0 text-success">{{ $comparison['percentage_point_improvement'] >= 0 ? '+' : '' }}{{ number_format((float) $comparison['percentage_point_improvement'], 1) }}</div><div class="small text-secondary">सापेक्ष सुधारणा: {{ number_format((float) $comparison['relative_improvement_percent'], 1) }}%</div></div></div></div></section>
    @endif

    <section class="mb-4"><h2 class="h4 portal-brand mb-3">कौशल्य विश्लेषण</h2><div class="row g-3">
        @foreach (data_get($attempt->diagnosis, 'skills', []) as $skill)
            <div class="col-md-6"><article class="card h-100 border-{{ $skill['classification'] === 'strength' ? 'success' : ($skill['classification'] === 'needs_support' ? 'warning' : 'primary') }}"><div class="card-body"><div class="d-flex justify-content-between gap-3"><div><h3 class="h5 mb-1">{{ $skill['skill_name_marathi'] }}</h3><div class="text-secondary small">{{ $skill['skill_name'] }}</div></div><div class="h3 mb-0">{{ number_format((float) $skill['accuracy'], 0) }}%</div></div>@if ($skill['errors'] !== [])<hr><strong class="small">सामान्य चुका</strong><div class="mt-2">@foreach ($skill['errors'] as $error)<span class="badge text-bg-light me-1">{{ $error['name_marathi'] ?: $error['name'] }} · {{ $error['count'] }}</span>@endforeach</div>@endif</div></article></div>
        @endforeach
    </div></section>

    <section><h2 class="h4 portal-brand mb-3">उत्तर पुनरावलोकन</h2><div class="vstack gap-3">
        @foreach ($attempt->answers as $answer)
            @php
                $question = $questions->get($answer->question_id);
                $submittedValue = data_get($answer->answer, 'value');
                $correctValue = data_get($answer->answer, 'correct_answer');
                $selectionType = in_array($question?->type, ['mcq', 'image_selection', 'audio_selection'], true);
                $submittedLabel = $selectionType
                    ? $question->options->whereIn('id', (array) $submittedValue)->map(fn ($option) => $option->label_marathi ?: $option->label)->implode(', ')
                    : (is_array($submittedValue) ? json_encode($submittedValue, JSON_UNESCAPED_UNICODE) : $submittedValue);
                $correctLabel = match ($question?->type) {
                    'mcq', 'image_selection', 'audio_selection' => $question->options->whereIn('id', (array) $correctValue)->map(fn ($option) => $option->label_marathi ?: $option->label)->implode(', '),
                    'number_input' => data_get($correctValue, 'value'),
                    'text_input' => implode(', ', data_get($correctValue, 'accepted', [])),
                    default => is_array($correctValue) ? json_encode($correctValue, JSON_UNESCAPED_UNICODE) : $correctValue,
                };
            @endphp
            @continue($question === null)
            <article class="card border-{{ $answer->is_correct ? 'success' : 'danger' }}"><div class="card-body"><div class="d-flex justify-content-between gap-3"><h3 class="h5">{{ $question->prompt_marathi }}</h3><span class="badge text-bg-{{ $answer->is_correct ? 'success' : 'danger' }}">{{ $answer->is_correct ? 'बरोबर' : 'चुकीचे' }}</span></div><p class="text-secondary">{{ $question->prompt }}</p><div><strong>उत्तर:</strong> {{ $submittedLabel ?: 'उत्तर दिले नाही' }}</div>@unless ($answer->is_correct)<div><strong>योग्य उत्तर:</strong> {{ $correctLabel }}</div>@endunless</div></article>
        @endforeach
    </div></section>
    <div class="mt-4"><a class="btn btn-primary" href="{{ $isMentorView ? route('tests.show', $attempt->test) : route('assessments.index') }}">{{ $isMentorView ? 'चाचणी निकाल' : 'माझ्या चाचण्या' }}</a></div>
</x-layouts.app>
