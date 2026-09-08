<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top" aria-label="Primary navigation">
        <div class="container">
            <a class="navbar-brand portal-brand fw-bold" href="{{ route('dashboard') }}">
                <i class="bi bi-book-half me-2" aria-hidden="true"></i>अध्ययन पोर्टल
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#portalNavigation" aria-controls="portalNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="portalNavigation">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    @if (auth()->user()->hasRole(\App\Enums\RoleCode::SuperAdmin))
                        <a class="nav-link" href="{{ route('schools.index') }}">शाळा</a>
                        <a class="nav-link" href="{{ route('subjects.index') }}">अभ्यासक्रम</a>
                    @endif
                    @if (auth()->user()->hasRole(\App\Enums\RoleCode::SchoolAdmin))
                        <a class="nav-link" href="{{ route('students.index') }}">विद्यार्थी</a>
                        <a class="nav-link" href="{{ route('mentors.index') }}">मार्गदर्शक</a>
                        <a class="nav-link" href="{{ route('school-classes.index') }}">वर्ग</a>
                    @endif
                    @if (auth()->user()->hasRole(\App\Enums\RoleCode::SuperAdmin, \App\Enums\RoleCode::SchoolAdmin, \App\Enums\RoleCode::Mentor))
                        <a class="nav-link" href="{{ route('activities.index') }}">उपक्रम</a>
                    @endif
                    <span class="navbar-text">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>बाहेर पडा
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    <main class="container py-4">
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
