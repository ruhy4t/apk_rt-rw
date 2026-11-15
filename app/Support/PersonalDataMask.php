<?php

namespace App\Support;

final class PersonalDataMask
{
    public static function number(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $value);
        $length = strlen($digits);
        if ($length <= 3) {
            return str_repeat('*', max(1, $length));
        }

        return substr($digits, 0, 2).str_repeat('*', $length - 3).substr($digits, -1);
    }
}
