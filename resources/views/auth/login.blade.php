<x-layouts.guest title="Login">
    <span class="badge rounded-pill text-bg-warning mb-3">सुरक्षित प्रवेश</span>
    <h1 class="h3 portal-brand">पोर्टलमध्ये प्रवेश करा</h1>
    <p class="text-secondary">तुमचा शाळेचा ईमेल आणि पासवर्ड वापरा.</p>

    <form method="POST" action="{{ route('login.store') }}" class="d-grid gap-3">
        @csrf
        <div>
            <label class="form-label" for="email">ईमेल</label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label class="form-label" for="password">पासवर्ड</label>
            <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-check">
            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
            <label class="form-check-label" for="remember">मला लक्षात ठेवा</label>
        </div>
        <button class="btn btn-primary btn-lg" type="submit">प्रवेश करा</button>
        <a class="text-center" href="{{ route('password.request') }}">पासवर्ड विसरलात?</a>
    </form>
</x-layouts.guest>
