<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['village_name', 'district_name', 'city_name'] as $name) {
            if (! Schema::hasColumn('rw_areas', $name)) {
                Schema::table('rw_areas', fn (Blueprint $table) => $table->string($name, 120)->nullable());
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Identitas wilayah tidak dihapus melalui rollback. Gunakan pemulihan yang telah disetujui.');
    }
};
