<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Services\UserSessions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:160']]);
        DB::transaction(function () use ($request, $data) {
            $request->user()->update($data);
            AuditEvent::record($request->user(), 'user.profile_updated', $request->user());
        });

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request, UserSessions $sessions): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed'],
        ]);
        if (Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.']);
        }

        DB::transaction(function () use ($request, $data, $sessions) {
            $user = $request->user();
            $user->forceFill(['password' => $data['password'], 'must_change_password' => false, 'remember_token' => null])->save();
            $sessions->revoke($user, $request->session()->getId());
            AuditEvent::record($user, 'user.password_changed', $user);
        });
        $request->session()->put('auth.password_fingerprint', hash('sha256', $request->user()->getAuthPassword()));
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Kata sandi berhasil diubah.');
    }
}
