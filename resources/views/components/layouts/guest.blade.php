<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <a class="d-inline-flex align-items-center gap-2 mb-4 text-decoration-none portal-brand fw-bold" href="{{ url('/') }}">
                    <i class="bi bi-book-half" aria-hidden="true"></i>
                    <span>मराठी आणि गणित अध्ययन पोर्टल</span>
                </a>
                <section class="card portal-card">
                    <div class="card-body p-4 p-md-5">
                        @if (session('status'))
                            <div class="alert alert-success" role="status">{{ session('status') }}</div>
                        @endif
                        {{ $slot }}
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
