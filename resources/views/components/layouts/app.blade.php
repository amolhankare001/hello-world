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
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
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
        {{ $slot }}
    </main>
</body>
</html>
