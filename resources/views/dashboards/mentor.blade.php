<x-layouts.app title="Mentor dashboard">
    <header class="mb-4">
        <span class="badge text-bg-success mb-2">MENTOR</span>
        <h1 class="h2 portal-brand">माझे विद्यार्थी</h1>
        <p class="text-secondary mb-0">सध्या नियुक्त विद्यार्थ्यांसाठी वैयक्तिक मदत.</p>
    </header>
    <section class="card portal-card">
        <div class="card-body">
            @if ($assignments->isEmpty())
                <p class="mb-0 text-secondary">सध्या कोणताही विद्यार्थी नियुक्त केलेला नाही.</p>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($assignments as $assignment)
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <h2 class="h6 mb-1">{{ $assignment->student->user->name }}</h2>
                                <span class="text-secondary">{{ $assignment->student->student_number }}</span>
                            </div>
                            @if ($assignment->is_primary)
                                <span class="badge text-bg-light">Primary</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.app>
