<x-layouts.app title="Interactive simulations">
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
        <div>
            <span class="badge rounded-pill text-bg-success mb-2">हाताळून शिकूया</span>
            <h1 class="h2 portal-brand mb-1">माझी संवादात्मक अनुकरणे</h1>
            <p class="text-secondary mb-0">वस्तू हलवा, संख्या बांधा आणि शब्द योग्य क्रमाने जोडा.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('student.dashboard') }}">माझी प्रगती</a>
    </div>

    <div class="row g-4">
        @forelse ($simulations as $simulation)
            <div class="col-md-6 col-xl-4">
                <article class="card portal-card simulation-catalog-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="simulation-catalog-icon" aria-hidden="true">
                                {{ data_get($simulation->configuration, 'icon', '🧩') }}
                            </div>
                            <span class="badge text-bg-light text-success">
                                {{ $simulation->skills->first()?->subject?->name_marathi }}
                            </span>
                        </div>
                        <h2 class="h4 mt-4 mb-1">{{ $simulation->title_marathi }}</h2>
                        <div class="text-secondary mb-3">{{ $simulation->title }}</div>
                        <p>{{ $simulation->description_marathi ?: $simulation->description }}</p>
                        <div class="d-flex flex-wrap gap-2 small">
                            <span class="badge rounded-pill text-bg-light">
                                {{ data_get($simulation->configuration, 'challenge_count', 5) }} कृती
                            </span>
                            <span class="badge rounded-pill text-bg-light">
                                वस्तू हाताळा
                            </span>
                        </div>
                    </div>
                    <div class="card-footer border-0 bg-transparent p-4 pt-0">
                        <form method="POST" action="{{ route('simulations.start', $simulation) }}">
                            @csrf
                            <button class="btn btn-success btn-lg w-100" type="submit">
                                अनुकरण सुरू करा
                            </button>
                        </form>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">सध्या कोणतेही प्रकाशित अनुकरण उपलब्ध नाही.</div>
            </div>
        @endforelse
    </div>

    @if ($recentSessions->isNotEmpty())
        <section class="card portal-card mt-5">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">अलीकडील अनुकरणे</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>अनुकरण</th><th>गुण</th><th>अचूकता</th><th>XP</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($recentSessions as $session)
                            <tr>
                                <td>{{ $session->simulation->title_marathi }}</td>
                                <td>{{ $session->result?->score }} / {{ $session->result?->max_score }}</td>
                                <td>{{ number_format((float) $session->result?->accuracy, 0) }}%</td>
                                <td>+{{ $session->result?->xp_awarded }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-success" href="{{ route('simulations.sessions.result', $session) }}">
                                        निकाल पहा
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</x-layouts.app>
