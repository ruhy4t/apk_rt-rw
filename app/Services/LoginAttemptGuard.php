<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Timebox;

class LoginAttemptGuard
{
    public function authenticate(string $username, #[\SensitiveParameter] string $password): array
    {
        // The explicit shared database store is required on Vercel; instance-local
        // caches cannot enforce one account limit across devices and instances.
        $cache = Cache::store('database');
        $key = 'login-attempts:'.hash_hmac('sha256', $username, (string) config('app.key'));
        try {
            return (new Timebox)->call(fn () => $cache->lock($key.':lock', 30)->block(5, function () use ($cache, $key, $username, $password) {
                $state = $cache->get($key, ['failures' => 0, 'locked_until' => 0]);
                if ($state['locked_until'] > now()->timestamp) {
                    return ['user' => null, 'message' => $this->lockedMessage($state['locked_until'] - now()->timestamp)];
                }
                if ($state['locked_until'] !== 0) {
                    $state = ['failures' => 0, 'locked_until' => 0];
                }
                $user = User::query()->withUsername($username)->first();
                $credentials = ['password' => $password];
                if ($user && $user->is_active && Auth::getProvider()->validateCredentials($user, $credentials)) {
                    Auth::getProvider()->rehashPasswordIfRequired($user, $credentials);
                    $cache->forget($key);

                    return ['user' => $user, 'message' => null];
                }

                $state['failures']++;
                if ($state['failures'] >= 5) {
                    $state['locked_until'] = now()->addMinutes(5)->timestamp;
                    $cache->put($key, $state, 300);

                    return ['user' => null, 'message' => $this->lockedMessage(300)];
                }
                // Existing account counters survive until a successful login or
                // lock expiry. Unknown names expire to bound storage use.
                $user ? $cache->forever($key, $state) : $cache->put($key, $state, 300);

                return ['user' => null, 'message' => 'Nama pengguna atau kata sandi tidak sesuai. Periksa kembali, lalu coba masuk lagi.'];
            }), 200000);
        } catch (LockTimeoutException) {
            return ['user' => null, 'message' => 'Percobaan masuk lain sedang diproses. Tunggu beberapa detik, lalu coba lagi.'];
        } catch (QueryException) {
            return ['user' => null, 'message' => 'Masuk sementara tidak tersedia. Coba lagi beberapa saat atau hubungi pengurus.'];
        }
    }

    private function lockedMessage(int $seconds): string
    {
        return 'Akses masuk akun dikunci sementara setelah 5 percobaan gagal. Coba lagi dalam '.max(1, (int) ceil($seconds / 60)).' menit. Mengganti perangkat atau jaringan tidak membuka kunci.';
    }
}
