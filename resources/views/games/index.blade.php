<x-layouts.app title="Learning games">
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
        <div>
            <span class="badge rounded-pill text-bg-primary mb-2">खेळातून शिकूया</span>
            <h1 class="h2 portal-brand mb-1">माझे अध्ययन खेळ</h1>
            <p class="text-secondary mb-0">योग्य पर्याय पकडा, गुण मिळवा आणि तुमचे कौशल्य वाढवा.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('student.dashboard') }}">माझी प्रगती</a>
    </div>

    <div class="row g-4">
        @forelse ($games as $game)
            @php
                $firstLevel = $game->levels->first();
            @endphp
            <div class="col-md-6">
                <article class="card portal-card game-catalog-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="game-catalog-icon" aria-hidden="true">
                                {{ data_get($game->configuration, 'icon', '★') }}
                            </div>
                            <span class="badge text-bg-light text-primary">
                                {{ $game->skills->first()?->subject?->name_marathi }}
                            </span>
                        </div>
                        <h2 class="h4 mt-4 mb-1">{{ $game->title_marathi }}</h2>
                        <div class="text-secondary mb-3">{{ $game->title }}</div>
                        <p>{{ $game->description_marathi ?: $game->description }}</p>
                        <div class="d-flex flex-wrap gap-2 small">
                            <span class="badge rounded-pill text-bg-light">
                                {{ $game->levels->count() }} पातळ्या
                            </span>
                            <span class="badge rounded-pill text-bg-light">
                                {{ data_get($firstLevel?->configuration, 'lives', 3) }} संधी
                            </span>
                            @if ($firstLevel?->time_limit_seconds)
                                <span class="badge rounded-pill text-bg-light">
                                    {{ $firstLevel->time_limit_seconds }} सेकंद
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer border-0 bg-transparent p-4 pt-0">
                        <form method="POST" action="{{ route('games.start', $game) }}">
                            @csrf
                            <button class="btn btn-primary btn-lg w-100" type="submit">
                                खेळ सुरू करा
                            </button>
                        </form>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">सध्या कोणताही प्रकाशित अध्ययन खेळ उपलब्ध नाही.</div>
            </div>
        @endforelse
    </div>

    @if ($recentSessions->isNotEmpty())
        <section class="card portal-card mt-5">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">अलीकडील खेळ</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>खेळ</th>
                            <th>गुण</th>
                            <th>अचूकता</th>
                            <th>XP</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentSessions as $session)
                            <tr>
                                <td>{{ $session->game->title_marathi }}</td>
                                <td>{{ $session->result?->score }} / {{ $session->result?->max_score }}</td>
                                <td>{{ number_format((float) $session->result?->accuracy, 0) }}%</td>
                                <td>+{{ $session->result?->xp_awarded }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('games.sessions.result', $session) }}">
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
