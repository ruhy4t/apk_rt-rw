<?php

namespace App\Services;

use Illuminate\Contracts\Session\Session;

class LoginCaptcha
{
    public function create(Session $session): string
    {
        $first = random_int(1, 9);
        $second = random_int(1, 9);
        $session->put('login_captcha', ['answer' => hash('sha256', (string) ($first + $second)), 'expires' => now()->addMinutes(10)->timestamp]);

        return $first.' + '.$second.' = ?';
    }

    public function verify(Session $session, string $answer): bool
    {
        $challenge = $session->pull('login_captcha');

        return is_array($challenge)
            && isset($challenge['answer'], $challenge['expires'])
            && $challenge['expires'] >= now()->timestamp
            && hash_equals($challenge['answer'], hash('sha256', trim($answer)));
    }
}
