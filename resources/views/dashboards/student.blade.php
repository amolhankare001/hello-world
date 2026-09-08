<x-layouts.app title="माझा शिकण्याचा प्रवास">
    <header class="student-hero portal-card mb-4 p-4 p-lg-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge rounded-pill text-bg-light text-primary mb-3">माझा शिकण्याचा प्रवास</span>
                <h1 class="display-6 fw-bold mb-2">नमस्कार, {{ auth()->user()->name }}! 👋</h1>
                <p class="fs-5 mb-0">
                    @if ($streak->current_days > 0)
                        🔥 सलग {{ $streak->current_days }} दिवस शिकत आहेस!
                    @else
                        आज एक नवीन अध्ययन प्रवास सुरू करूया.
                    @endif
                </p>
            </div>
            <div class="col-lg-5">
                <div class="row g-2 text-center">
                    <div class="col-4"><div class="student-stat"><strong>{{ $currentXp }}</strong><span>⭐ XP</span></div></div>
                    <div class="col-4"><div class="student-stat"><strong>{{ $level }}</strong><span>पातळी</span></div></div>
                    <div class="col-4"><div class="student-stat"><strong>{{ $badgeCount }}</strong><span>🏆 बॅज</span></div></div>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between small mb-1"><span>पुढील पातळी</span><span>{{ $levelProgress }} / {{ $levelSize }} XP</span></div>
                    <div class="progress student-progress" role="progressbar" aria-label="पुढील पातळीची प्रगती" aria-valuenow="{{ $levelProgress }}" aria-valuemin="0" aria-valuemax="{{ $levelSize }}">
                        <div class="progress-bar bg-warning" style="width: {{ ($levelProgress / $levelSize) * 100 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <article class="card portal-card h-100"><div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div><span class="student-icon bg-warning-subtle">🎯</span><h2 class="h4 d-inline ms-2">आजचे लक्ष्य</h2></div>
                    <strong>{{ $dailyGoal->completed_activities }} / {{ $dailyGoal->target_activities }}</strong>
                </div>
                @php
                    $goalPercentage = min(100, ($dailyGoal->completed_activities / max(1, $dailyGoal->target_activities)) * 100);
                @endphp
                <div class="progress student-progress mb-3" role="progressbar" aria-label="आजच्या लक्ष्याची प्रगती" aria-valuenow="{{ $dailyGoal->completed_activities }}" aria-valuemin="0" aria-valuemax="{{ $dailyGoal->target_activities }}">
                    <div class="progress-bar bg-success" style="width: {{ $goalPercentage }}%"></div>
                </div>
                <p class="text-secondary mb-0">
                    @if ($dailyGoal->completed_at)
                        छान! आजचे लक्ष्य पूर्ण झाले.
                    @else
                        आणखी {{ max(0, $dailyGoal->target_activities - $dailyGoal->completed_activities) }} कृती पूर्ण कर.
                    @endif
                </p>
            </div></article>
        </div>
        <div class="col-lg-7">
            <h2 class="h4 mb-3">माझी कौशल्य प्रगती</h2>
            <div class="row g-3 h-100">
                @forelse ($subjectProgress as $progress)
                    <div class="col-md-6">
                        <article class="card portal-card subject-progress-card h-100">
                            <div class="card-body p-4 d-flex flex-column">
                                <span class="student-icon {{ $progress['subject']->code === 'MARATHI' ? 'bg-danger-subtle' : 'bg-info-subtle' }}">
                                    {{ $progress['subject']->code === 'MARATHI' ? 'अ' : '१२३' }}
                                </span>
                                <h2 class="h4 mt-3">{{ $progress['subject']->name_marathi ?: $progress['subject']->name }}</h2>
                                <div class="d-flex justify-content-between small mb-1"><span>माझी प्रगती</span><strong>{{ $progress['mastery'] }}%</strong></div>
                                <div class="progress student-progress mb-3" role="progressbar" aria-label="{{ $progress['subject']->name_marathi }} प्रगती" aria-valuenow="{{ $progress['mastery'] }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" style="width: {{ $progress['mastery'] }}%"></div>
                                </div>
                                <p class="text-secondary small">{{ $progress['mastered_skills'] }} / {{ $progress['tracked_skills'] }} कौशल्ये पूर्ण</p>
                                <a class="btn btn-primary mt-auto student-touch-target" href="{{ route('practice.index') }}">पुढे शिकूया</a>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12"><div class="alert alert-light h-100 mb-0">विषयांची प्रगती लवकरच येथे दिसेल.</div></div>
                @endforelse
            </div>
        </div>
    </div>

    <section class="mb-4" aria-labelledby="recommended-heading">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h3 mb-0" id="recommended-heading">आजचा माझा सराव</h2>
            <a href="{{ route('practice.index') }}">सर्व सराव</a>
        </div>
        <div class="row g-3">
            @forelse ($recommendedActivities as $activity)
                <div class="col-md-6 col-xl-4">
                    <article class="card portal-card h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <span class="badge text-bg-light text-primary align-self-start mb-3">{{ $activity->skill->subject->name_marathi }}</span>
                            <h3 class="h5">{{ $activity->title_marathi ?: $activity->title }}</h3>
                            <p class="text-secondary">{{ $activity->skill->name_marathi }} · {{ $activity->estimated_minutes }} मिनिटे</p>
                            <form class="mt-auto" method="POST" action="{{ route('practice.start', $activity) }}">
                                @csrf
                                <button class="btn btn-primary w-100 student-touch-target" type="submit">सराव सुरू करा</button>
                            </form>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12"><div class="alert alert-light">आजचा सराव तयार होत आहे.</div></div>
            @endforelse
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <section class="card portal-card h-100" aria-labelledby="journey-heading"><div class="card-body p-4">
                <h2 class="h3 mb-4" id="journey-heading">माझा शिकण्याचा प्रवास</h2>
                <div class="learning-journey">
                    @forelse ($learningJourney as $step)
                        <div class="journey-step journey-{{ $step['state'] }}">
                            <span class="journey-marker">
                                @if ($step['state'] === 'completed') ✓ @elseif ($step['state'] === 'unlocked') 🔓 @else 🔒 @endif
                            </span>
                            <div>
                                <strong>{{ $step['skill']->name_marathi ?: $step['skill']->name }}</strong>
                                <div class="small text-secondary">
                                    @if ($step['state'] === 'completed') पूर्ण @elseif ($step['state'] === 'unlocked') आता शिकूया @else पुढील टप्पा @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">तुझा अध्ययन मार्ग तयार होत आहे.</p>
                    @endforelse
                </div>
            </div></section>
        </div>
        <div class="col-lg-5">
            <article class="card portal-card h-100"><div class="card-body p-4 d-flex flex-column">
                <span class="student-icon bg-success-subtle">🎮</span>
                <h2 class="h3 mt-3">आजचा खेळ</h2>
                @if ($todayGame)
                    <h3 class="h5">{{ $todayGame->title_marathi ?: $todayGame->title }}</h3>
                    <p class="text-secondary">{{ $todayGame->description_marathi ?: $todayGame->description }}</p>
                    <span class="btn btn-outline-primary disabled mt-auto student-touch-target" aria-disabled="true">खेळ लवकरच सुरू होईल</span>
                @else
                    <p class="text-secondary">नवीन अध्ययन खेळ लवकरच येथे येईल.</p>
                @endif
            </div></article>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <section class="card portal-card h-100" aria-labelledby="badges-heading"><div class="card-body p-4">
                <h2 class="h3 mb-3" id="badges-heading">माझे बॅज</h2>
                <div class="row g-2">
                    @forelse ($recentBadges as $studentBadge)
                        <div class="col-6">
                            <div class="student-award h-100">
                                <span aria-hidden="true">🏆</span>
                                <strong>{{ $studentBadge->badge->name_marathi ?: $studentBadge->badge->name }}</strong>
                            </div>
                        </div>
                    @empty
                        <div class="col-12"><p class="text-secondary mb-0">पहिली कृती पूर्ण करून पहिला बॅज मिळव.</p></div>
                    @endforelse
                </div>
            </div></section>
        </div>
        <div class="col-lg-6">
            <section class="card portal-card h-100" aria-labelledby="achievements-heading"><div class="card-body p-4">
                <h2 class="h3 mb-3" id="achievements-heading">माझी कामगिरी</h2>
                <div class="d-grid gap-2">
                    @forelse ($recentAchievements as $achievement)
                        <div class="student-award"><span aria-hidden="true">🌟</span><strong>{{ $achievement->title_marathi ?: $achievement->title }}</strong></div>
                    @empty
                        <p class="text-secondary mb-0">तुझी वैयक्तिक कामगिरी लवकरच येथे दिसेल.</p>
                    @endforelse
                </div>
            </div></section>
        </div>
    </div>
</x-layouts.app>
