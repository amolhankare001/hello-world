<x-layouts.app title="Classes">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h2 portal-brand mb-1">वर्ग आणि तुकड्या</h1><p class="text-secondary mb-0">शाळेची शैक्षणिक रचना व्यवस्थापित करा.</p></div>
        <a class="btn btn-primary" href="{{ route('school-classes.create') }}">नवीन वर्ग</a>
    </div>
    <div class="row g-3">
        @forelse ($classes as $schoolClass)
            <div class="col-md-6 col-xl-4">
                <article class="card portal-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-3">
                            <div><span class="badge text-bg-light mb-2">इयत्ता {{ $schoolClass->grade_level }}</span><h2 class="h5">{{ $schoolClass->name_marathi ?: $schoolClass->name }}</h2></div>
                            <span class="badge {{ $schoolClass->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $schoolClass->is_active ? 'सक्रिय' : 'निष्क्रिय' }}</span>
                        </div>
                        <p class="text-secondary mb-3">तुकड्या: {{ $schoolClass->divisions->pluck('name_marathi')->filter()->join(', ') ?: $schoolClass->divisions->pluck('name')->join(', ') }}</p>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('school-classes.edit', $schoolClass) }}">संपादित करा</a>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">वर्ग उपलब्ध नाहीत.</div></div>
        @endforelse
    </div>
</x-layouts.app>
