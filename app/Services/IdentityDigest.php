<?php

namespace App\Services;

use RuntimeException;

class IdentityDigest
{
    public function make(string $type, string $value): string
    {
        $key = config('app.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Kunci aplikasi tidak tersedia untuk melindungi identitas.');
        }

        return hash_hmac('sha256', $type.':'.$value, hash_hmac('sha256', 'identity-digest-v1', $key, true));
    }
}
