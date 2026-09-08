<x-layouts.app title="Edit learning activity">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h2 portal-brand mb-1">{{ $activity->title_marathi }}</h1><p class="text-secondary mb-0">{{ $activity->title }}</p></div><a class="btn btn-outline-secondary" href="{{ route('activities.index') }}">उपक्रम सूची</a></div>
    <form method="POST" action="{{ route('activities.update', $activity) }}" class="card portal-card">
        @csrf
        @method('PUT')
        <div class="card-body p-4">@include('content.activities._form')</div>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">बदल जतन करा</button></div>
    </form>

    @if ($activity->type === 'practice' && $activity->practiceActivity)
        @php($practiceConfiguration = $activity->practiceActivity->configuration ?? [])
        <section class="card portal-card mt-4">
            <div class="card-header bg-white"><h2 class="h5 mb-0">सराव रचना / Practice settings</h2></div>
            <form method="POST" action="{{ route('activities.practice.update', $activity) }}">
                @csrf
                @method('PUT')
                <div class="card-body row g-3">
                    <div class="col-md-4"><label class="form-label" for="question_count">प्रश्न संख्या</label><input class="form-control" id="question_count" name="question_count" type="number" min="1" max="100" value="{{ old('question_count', $activity->practiceActivity->question_count) }}" required></div>
                    <div class="col-md-4"><label class="form-label" for="difficulty_up_accuracy">कठीणपणा वाढवा (%)</label><input class="form-control" id="difficulty_up_accuracy" name="difficulty_up_accuracy" type="number" min="0" max="100" value="{{ old('difficulty_up_accuracy', data_get($practiceConfiguration, 'difficulty_up_accuracy', 80)) }}" required></div>
                    <div class="col-md-4"><label class="form-label" for="remedial_accuracy">उपचारात्मक मदत (%)</label><input class="form-control" id="remedial_accuracy" name="remedial_accuracy" type="number" min="0" max="100" value="{{ old('remedial_accuracy', data_get($practiceConfiguration, 'remedial_accuracy', 60)) }}" required></div>
                    <div class="col-md-3"><label class="form-label" for="minimum_difficulty">किमान पातळी</label><input class="form-control" id="minimum_difficulty" name="minimum_difficulty" type="number" min="1" max="5" value="{{ old('minimum_difficulty', data_get($practiceConfiguration, 'minimum_difficulty', 1)) }}" required></div>
                    <div class="col-md-3"><label class="form-label" for="maximum_difficulty">कमाल पातळी</label><input class="form-control" id="maximum_difficulty" name="maximum_difficulty" type="number" min="1" max="5" value="{{ old('maximum_difficulty', data_get($practiceConfiguration, 'maximum_difficulty', 5)) }}" required></div>
                    <div class="col-md-3 form-check align-self-end mb-2"><input name="randomize_questions" type="hidden" value="0"><input class="form-check-input" id="randomize_questions" name="randomize_questions" type="checkbox" value="1" @checked(old('randomize_questions', $activity->practiceActivity->randomize_questions))><label class="form-check-label" for="randomize_questions">प्रश्न मिसळा</label></div>
                    <div class="col-md-3 form-check align-self-end mb-2"><input name="show_feedback_immediately" type="hidden" value="0"><input class="form-check-input" id="show_feedback_immediately" name="show_feedback_immediately" type="checkbox" value="1" @checked(old('show_feedback_immediately', $activity->practiceActivity->show_feedback_immediately))><label class="form-check-label" for="show_feedback_immediately">लगेच अभिप्राय</label></div>
                </div>
                <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">सराव सेटिंग्ज जतन करा</button></div>
            </form>
        </section>

        <section class="card portal-card mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">प्रश्नसंच / Question bank</h2><span class="badge text-bg-secondary">{{ $activity->questions->count() }}</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>प्रश्न</th><th>प्रकार</th><th>पातळी</th><th>स्थिती</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($activity->questions as $question)
                            <tr>
                                <td><strong>{{ $question->prompt_marathi }}</strong><div class="small text-secondary">{{ $question->prompt }}</div></td>
                                <td>{{ str_replace('_', ' ', $question->type) }}</td>
                                <td>{{ $question->difficulty }}</td>
                                <td>{{ $question->is_active ? 'सक्रिय' : 'निष्क्रिय' }}</td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('activities.questions.edit', [$activity, $question]) }}">संपादित करा</a></td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-secondary py-4" colspan="5">अजून प्रश्न नाहीत.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card portal-card mt-4">
            <div class="card-header bg-white"><h2 class="h5 mb-0">नवीन प्रश्न / Add question</h2></div>
            <form method="POST" action="{{ route('activities.questions.store', $activity) }}">
                @csrf
                <div class="card-body p-4">@include('content.questions._form', ['question' => null, 'optionsText' => ''])</div>
                <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit">प्रश्न जोडा</button></div>
            </form>
        </section>
    @endif
</x-layouts.app>
