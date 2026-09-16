<x-layouts.guest title="Reset password">
    <h1 class="h3 portal-brand">नवीन पासवर्ड तयार करा</h1>
    <form method="POST" action="{{ route('password.update') }}" class="d-grid gap-3">
        @csrf
        <input name="token" type="hidden" value="{{ $token }}">
        <div>
            <label class="form-label" for="email">ईमेल</label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $email) }}" required readonly>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label class="form-label" for="password">नवीन पासवर्ड</label>
            <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required autocomplete="new-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label class="form-label" for="password_confirmation">पासवर्ड पुन्हा लिहा</label>
            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>
        <button class="btn btn-primary" type="submit">पासवर्ड जतन करा</button>
    </form>
</x-layouts.guest>
