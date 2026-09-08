<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <section class="card portal-card overflow-hidden">
                    <div class="card-body p-4 p-md-5">
                        <span class="badge rounded-pill text-bg-warning mb-3">Phase 1 foundation</span>
                        <h1 class="display-5 fw-bold portal-brand">मराठी आणि गणित अध्ययन पोर्टल</h1>
                        <p class="lead mb-4">
                            Personalized learning, assessment, mentor support, and holistic progress in one
                            secure school platform.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="border rounded-4 p-3 h-100">
                                    <i class="bi bi-translate fs-2 portal-brand" aria-hidden="true"></i>
                                    <h2 class="h5 mt-2">Marathi-first</h2>
                                    <p class="mb-0 text-secondary">Bilingual content with accessible learning paths.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-4 p-3 h-100">
                                    <i class="bi bi-graph-up-arrow fs-2 portal-brand" aria-hidden="true"></i>
                                    <h2 class="h5 mt-2">Evidence-led</h2>
                                    <p class="mb-0 text-secondary">Skill history, mastery, and improvement tracking.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-4 p-3 h-100">
                                    <i class="bi bi-people fs-2 portal-brand" aria-hidden="true"></i>
                                    <h2 class="h5 mt-2">Mentor-supported</h2>
                                    <p class="mb-0 text-secondary">Focused interventions without competitive rankings.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
