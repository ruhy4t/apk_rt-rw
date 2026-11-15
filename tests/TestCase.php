<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function loginPayload(array $attributes): array
    {
        $this->withSession(['login_captcha' => ['answer' => hash('sha256', '13'), 'expires' => now()->addMinutes(10)->timestamp]]);

        return array_merge($attributes, ['captcha' => '13']);
    }
}
