<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LoginAttemptGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoginProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_login_requires_a_single_use_captcha_and_does_not_flash_password(): void
    {
        User::factory()->create(['username' => 'warga_uji', 'password' => 'password']);
        $this->get(route('login'))->assertOk()->assertSee('Jawab penjumlahan:');
        $this->post(route('login.store'), ['username' => 'warga_uji', 'password' => 'password'])->assertSessionHasErrors('captcha');
        $this->assertGuest();
        $payload = $this->loginPayload(['username' => 'warga_uji', 'password' => 'password']);
        $payload['captcha'] = '99';
        $this->post(route('login.store'), $payload)->assertSessionHasErrors('captcha');
        $this->post(route('login.store'), array_replace($payload, ['captcha' => '13']))->assertSessionHasErrors('captcha');
        $this->assertGuest();
        $this->assertFalse(session()->hasOldInput('password'));
        $this->post(route('login.store'), $this->loginPayload(['username' => 'warga_uji', 'password' => 'password']))->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_captcha_expires_and_captcha_errors_do_not_consume_password_attempts(): void
    {
        User::factory()->create(['username' => 'warga_uji', 'password' => 'password']);
        for ($i = 0; $i < 6; $i++) {
            $payload = $this->loginPayload(['username' => 'warga_uji', 'password' => 'password']);
            $this->travel(11)->minutes();
            $this->post(route('login.store'), $payload)->assertSessionHasErrors('captcha');
        }
        $this->assertDatabaseCount('cache', 0);
        $this->post(route('login.store'), $this->loginPayload(['username' => 'warga_uji', 'password' => 'password']))->assertRedirect(route('dashboard'));
    }

    public function test_fifth_password_failure_locks_account_across_ips_and_unlocks_after_five_minutes(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create(['username' => 'warga_uji', 'password' => 'password']);
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$i])->post(route('login.store'), $this->loginPayload(['username' => 'WARGA_UJI', 'password' => 'salah']));
            $response->assertSessionHasErrors('username');
            $this->assertGuest();
        }
        $this->assertStringContainsString('dikunci', session('errors')->first('username'));
        $this->post(route('login.store'), $this->loginPayload(['username' => 'warga_uji', 'password' => 'password']))->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->travel(299)->seconds();
        $this->post(route('login.store'), $this->loginPayload(['username' => 'warga_uji', 'password' => 'password']))->assertSessionHasErrors('username');
        $this->travel(1)->seconds();
        $this->post(route('login.store'), $this->loginPayload(['username' => 'warga_uji', 'password' => 'password']))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('cache', 0);
        $rawCache = DB::table('cache')->get()->toJson();
        $this->assertStringNotContainsString('warga_uji', $rawCache);
    }

    public function test_success_resets_failures_and_other_accounts_are_not_locked(): void
    {
        $first = User::factory()->create(['username' => 'pertama', 'password' => 'password']);
        $second = User::factory()->create(['username' => 'kedua', 'password' => 'password']);
        $guard = app(LoginAttemptGuard::class);
        for ($i = 0; $i < 4; $i++) {
            $this->assertNull($guard->authenticate('pertama', 'salah')['user']);
        }
        $this->assertSame($first->id, $guard->authenticate('pertama', 'password')['user']->id);
        $this->assertNull($guard->authenticate('pertama', 'salah')['user']);
        $this->assertSame($first->id, $guard->authenticate('pertama', 'password')['user']->id);
        for ($i = 0; $i < 5; $i++) {
            $guard->authenticate('pertama', 'salah');
        }
        $this->assertSame($second->id, $guard->authenticate('kedua', 'password')['user']->id);
    }

    public function test_unknown_user_and_inactive_user_receive_same_failure_and_lock_messages(): void
    {
        User::factory()->create(['username' => 'nonaktif', 'password' => 'password', 'is_active' => false]);
        $guard = app(LoginAttemptGuard::class);
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($guard->authenticate('tidak_ada', 'salah')['message'], $guard->authenticate('nonaktif', 'password')['message']);
        }
        $this->assertGuest();
        foreach (DB::table('cache')->get() as $row) {
            $this->assertStringNotContainsString('tidak_ada', $row->key);
            $this->assertStringNotContainsString('nonaktif', $row->key);
        }
    }

    public function test_missing_shared_cache_tables_does_not_allow_login(): void
    {
        User::factory()->create(['username' => 'warga_uji', 'password' => 'password']);
        config(['cache.stores.database.table' => 'unavailable_cache_for_test']);
        Cache::purge('database');
        $this->post(route('login.store'), $this->loginPayload(['username' => 'warga_uji', 'password' => 'password']))->assertSessionHasErrors('username');
        $this->assertStringContainsString('sementara tidak tersedia', session('errors')->first('username'));
        $this->assertGuest();
    }

    public function test_network_rate_limit_explains_when_to_retry(): void
    {
        $this->withMiddleware(ThrottleRequests::class);
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post(route('login.store'), [])->assertSessionHasErrors('captcha');
        }
        $this->post(route('login.store'), [])->assertRedirect(route('login'))->assertSessionHasErrors('username');
        $this->assertStringContainsString('Tunggu 1 menit', session('errors')->first('username'));
        $this->assertGuest();
    }
}
