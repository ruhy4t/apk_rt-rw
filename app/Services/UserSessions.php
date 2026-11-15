<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserSessions
{
    public function revoke(User $user, ?string $exceptId = null): void
    {
        $userConnection = $user->getConnectionName() ?? config('database.default');
        $sessionConnection = config('session.connection') ?: config('database.default');
        if (config('session.driver') !== 'database' || $sessionConnection !== $userConnection) {
            throw new RuntimeException('Pencabutan sesi memerlukan penyimpanan sesi database pada koneksi aplikasi.');
        }

        $sessions = DB::connection($user->getConnectionName())
            ->table(config('session.table'))
            ->where('user_id', $user->getKey());

        if ($exceptId !== null) {
            $sessions->where('id', '!=', $exceptId);
        }

        $sessions->delete();
    }
}
