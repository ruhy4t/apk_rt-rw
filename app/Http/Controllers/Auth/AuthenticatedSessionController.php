<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\LoginAttemptGuard;
use App\Services\LoginCaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request, LoginCaptcha $captcha): View
    {
        return view('auth.login', ['captchaQuestion' => $captcha->create($request->session())]);
    }

    public function store(LoginRequest $request, LoginCaptcha $captcha, LoginAttemptGuard $guard): RedirectResponse
    {
        if (! $captcha->verify($request->session(), $request->string('captcha')->toString())) {
            return redirect()->route('login')->withErrors(['captcha' => 'Jawaban penjumlahan salah atau sudah kedaluwarsa. Jawab soal baru di bawah ini.'])->onlyInput('username');
        }
        $credentials = $request->safe()->only('username', 'password');
        $username = mb_strtolower($credentials['username']);
        $result = $guard->authenticate($username, $credentials['password']);
        if (! $result['user']) {
            return redirect()->route('login')
                ->withErrors(['username' => $result['message']])
                ->onlyInput('username');
        }

        Auth::login($result['user']);
        $request->session()->regenerate();
        $request->session()->put('auth.password_fingerprint', hash('sha256', $request->user()->getAuthPassword()));

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
