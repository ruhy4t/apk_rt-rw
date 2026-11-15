<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin {username} {name}';

    protected $description = 'Create the initial application administrator account';

    public function handle(): int
    {
        $username = mb_strtolower((string) $this->argument('username'));

        if (! preg_match('/\A[a-z0-9_-]+\z/', $username)) {
            $this->components->error('Nama pengguna hanya boleh berisi huruf, angka, garis bawah, dan tanda hubung.');

            return self::FAILURE;
        }

        if (User::query()->where('username', $username)->exists()) {
            $this->components->error('Nama pengguna tersebut sudah digunakan.');

            return self::FAILURE;
        }

        $password = $this->secret('Kata sandi administrator (minimal 12 karakter)');

        if (! is_string($password) || mb_strlen($password) < 12) {
            $this->components->error('Kata sandi harus memiliki minimal 12 karakter.');

            return self::FAILURE;
        }

        $confirmation = $this->secret('Ulangi kata sandi');

        if (! is_string($confirmation) || ! hash_equals($password, $confirmation)) {
            $this->components->error('Konfirmasi kata sandi tidak cocok.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => (string) $this->argument('name'),
            'username' => $username,
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make($password),
        ]);

        $this->components->info('Akun administrator berhasil dibuat.');

        return self::SUCCESS;
    }
}
