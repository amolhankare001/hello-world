<x-layouts.guest title="Forgot password">
    <h1 class="h3 portal-brand">पासवर्ड रीसेट करा</h1>
    <p class="text-secondary">रीसेट लिंक मिळवण्यासाठी तुमचा ईमेल द्या.</p>
    <form method="POST" action="{{ route('password.email') }}" class="d-grid gap-3">
        @csrf
        <div>
            <label class="form-label" for="email">ईमेल</label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <button class="btn btn-primary" type="submit">रीसेट लिंक पाठवा</button>
        <a class="text-center" href="{{ route('login') }}">प्रवेश पानावर परत जा</a>
    </form>
</x-layouts.guest>
