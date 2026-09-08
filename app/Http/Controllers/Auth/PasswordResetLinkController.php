<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();

        if ($user?->canAccessPortal()) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('status', trans(Password::RESET_LINK_SENT));
    }
}
