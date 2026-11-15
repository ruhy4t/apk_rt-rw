<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $fingerprint = hash('sha256', $request->user()->getAuthPassword());
        $storedFingerprint = $request->session()->get('auth.password_fingerprint');
        if ($storedFingerprint !== null && ! hash_equals($storedFingerprint, $fingerprint)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => 'Sesi telah berakhir. Silakan masuk kembali.']);
        }
        $request->session()->put('auth.password_fingerprint', $fingerprint);

        if ($request->user()->must_change_password
            && ! $request->routeIs('profile.edit', 'profile.password', 'logout')) {
            return redirect()->route('profile.edit')->withErrors([
                'password' => 'Ganti kata sandi sementara sebelum menggunakan aplikasi.',
            ]);
        }

        return $next($request);
    }
}
